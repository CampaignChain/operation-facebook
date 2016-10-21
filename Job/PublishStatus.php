<?php
/*
 * Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CampaignChain\Operation\FacebookBundle\Job;

use CampaignChain\Channel\FacebookBundle\REST\FacebookClient;
use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Medium;
use CampaignChain\CoreBundle\EntityService\CTAService;
use CampaignChain\CoreBundle\Exception\ErrorCode;
use CampaignChain\CoreBundle\Exception\ExternalApiException;
use CampaignChain\CoreBundle\Exception\JobException;
use CampaignChain\Operation\FacebookBundle\Entity\StatusBase;
use CampaignChain\Operation\FacebookBundle\Entity\UserStatus;
use Doctrine\ORM\EntityManager;
use CampaignChain\CoreBundle\Job\JobActionInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use CampaignChain\Operation\FacebookBundle\Validator\PublishStatusValidator as Validator;

/**
 * Class PublishStatus
 * @package CampaignChain\Operation\FacebookBundle\Job
 */
class PublishStatus implements JobActionInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var CTAService
     */
    protected $cta;

    /**
     * @var FacebookClient
     */
    protected $client;

    /**
     * @var ReportPublishStatus
     */
    protected $report;

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var Validator
     */
    protected $validator;

    public function __construct(
        EntityManager $em,
        CTAService $cta,
        FacebookClient $client,
        ReportPublishStatus $report,
        CacheManager $cacheManager,
        Validator $validator
    )
    {
        $this->em = $em;
        $this->cta = $cta;
        $this->client = $client;
        $this->report = $report;
        $this->cacheManager = $cacheManager;
        $this->validator = $validator;
    }

    /**
     * @param  string $operationId
     * @return string
     * @throws \Exception
     */
    public function execute($operationId)
    {
        /** @var StatusBase $status */
        $status = $this->em
            ->getRepository('CampaignChainOperationFacebookBundle:StatusBase')
            ->findOneByOperation($operationId);

        if (!$status) {
            throw new \Exception('No Facebook status found for an operation with ID: '.$operationId);
        }

        // Check whether the message can be posted in the Location.
        $isExecutable = $this->validator->isExecutableByLocation($status, $status->getOperation()->getStartDate());
        if($isExecutable['status'] == false){
            throw new JobException($isExecutable['message'], ErrorCode::OPERATION_NOT_EXECUTABLE_IN_LOCATION);
        }

        /*
         * If it is a campaign or parent campaign with an interval (e.g.
         * repeating campaign), we make sure that every URL will be shortened to
         * avoid a duplicate status message error.
         */
        $options = array();
        if(
            $status->getOperation()->getActivity()->getCampaign()->getInterval() ||
            (
                $status->getOperation()->getActivity()->getCampaign()->getParent() &&
                $status->getOperation()->getActivity()->getCampaign()->getParent()->getInterval()
            )
        ){
            $options['shorten_all_unique'] = true;
        }

        /*
         * Process URLs in message and save the new message text, now including
         * the replaced URLs with the Tracking ID attached for call to action
         * tracking.
         */
        $status->setMessage(
            $this->cta->processCTAs($status->getMessage(), $status->getOperation(), $options)->getContent()
        );

        /** @var \Facebook $connection */
        $connection = $this->client->connectByActivity($status->getOperation()->getActivity());

        $paramsMsg = array();

        /*
         * If an image was attached, we'll first upload the photo to Facebook
         * and then use the Facebook object ID of the picture in the message.
         */
        $images = $this->em
            ->getRepository('CampaignChainHookImageBundle:Image')
            ->getImagesForOperation($status->getOperation());

        if ($images) {
            $paramsImg = array();

            $paramsImg['caption'] = $status->getMessage();
            // Avoid that feed shows "... added a new photo" entry automtically.
            $paramsImg['no_story'] = 1;

            //Facebook handles only 1 image
            $paramsImg['url'] = $this->cacheManager
                ->getBrowserPath($images[0]->getPath(), "auto_rotate");

            try {
                $responseImg = $connection->api('/'.$status->getFacebookLocation()->getIdentifier().'/photos', 'POST', $paramsImg);

                $paramsMsg['object_attachment'] = $responseImg['id'];
            } catch (\Exception $e) {
                throw new ExternalApiException($e->getMessage(), $e->getCode(), $e);
            }
        }

        if($status instanceof UserStatus){
            $privacy = array(
                'value' => $status->getPrivacy()
            );
            $paramsMsg['privacy'] = json_encode($privacy);
        }
        $paramsMsg['message'] = $status->getMessage();

        try {
            $responseMsg = $connection->api('/'.$status->getFacebookLocation()->getIdentifier().'/feed', 'POST', $paramsMsg);
        } catch (\Exception $e) {
            throw new ExternalApiException($e->getMessage(), $e->getCode(), $e);
        }

        $connection->destroySession();

        // Set URL to published status message on Facebook
        $statusURL = 'https://www.facebook.com/'.str_replace('_', '/posts/', $responseMsg['id']);

        $status->setUrl($statusURL);
        $status->setPostId($responseMsg['id']);
        // Set Operation to closed.
        $status->getOperation()->setStatus(Action::STATUS_CLOSED);

        $location = $status->getOperation()->getLocations()[0];
        $location->setIdentifier($responseMsg['id']);
        $location->setUrl($statusURL);
        $location->setName($status->getOperation()->getName());
        $location->setStatus(Medium::STATUS_ACTIVE);

        // Schedule data collection for report
        $this->report->schedule($status->getOperation());

        $this->em->flush();

        $this->message = 'The message "'.$paramsMsg['message'].'" with the ID "'.$responseMsg['id'].'" has been posted on Facebook';
        if($status instanceof UserStatus){
            $this->message .= ' with privacy setting "'.$privacy['value'].'"';
        }
        $this->message .= '. See it on Facebook: <a href="'.$statusURL.'">'.$statusURL.'</a>';

        return self::STATUS_OK;
    }

    public function getMessage()
    {
        return $this->message;
    }
}