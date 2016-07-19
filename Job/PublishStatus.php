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
use CampaignChain\CoreBundle\Exception\ExternalApiException;
use CampaignChain\Operation\FacebookBundle\Entity\StatusBase;
use CampaignChain\Operation\FacebookBundle\Entity\UserStatus;
use Doctrine\ORM\EntityManager;
use CampaignChain\CoreBundle\Job\JobActionInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

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
    protected $ctaService;

    /**
     * @var FacebookClient
     */
    protected $client;

    /**
     * @var ReportPublishStatus
     */
    protected $reportPublishStatus;

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var string
     */
    protected $message;

    public function __construct(EntityManager $em, CTAService $ctaService, FacebookClient $client, ReportPublishStatus $reportPublishStatus, CacheManager $cacheManager)
    {
        $this->em = $em;
        $this->ctaService = $ctaService;
        $this->client = $client;
        $this->reportPublishStatus = $reportPublishStatus;
        $this->cacheManager = $cacheManager;
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

        // Process URLs in message and save the new message text, now including
        // the replaced URLs with the Tracking ID attached for call to action tracking.
        $status->setMessage(
            $this->ctaService->processCTAs($status->getMessage(), $status->getOperation(), 'txt')->getContent()
        );

        /** @var \Facebook $connection */
        $connection = $this->client->connectByActivity($status->getOperation()->getActivity());

        if (!$connection) {
            return;
        }

        /*
         * If an image was attached, we'll first upload the photo to Facebook
         * and then use the Facebook object ID of the picture in the message.
         */
        $images = $this->em
            ->getRepository('CampaignChainHookImageBundle:Image')
            ->getImagesForOperation($status->getOperation());

        if ($images) {
            $paramsImg = array();
            // Suppress caption.
            $paramsImg['caption'] = $status->getMessage();
            // Avoid that feed shows "... added a new photo" entry automtically.
            //$paramsImg['no_story'] = 1;

            //Facebook handles only 1 image
            $paramsImg['url'] = $this->cacheManager
                ->getBrowserPath($images[0]->getPath(), "auto_rotate");

            try {
                $response = $connection->api('/'.$status->getFacebookLocation()->getIdentifier().'/photos', 'POST', $paramsImg);
                $paramsImg['object_attachment'] = $response['id'];
            } catch (\Exception $e) {
                throw new ExternalApiException($e->getMessage(), $e->getCode(), $e);
            }
        } else {
            $params = array();

            if ($status instanceof UserStatus) {
                $privacy = array(
                    'value' => $status->getPrivacy()
                );
                $params['privacy'] = json_encode($privacy);
            }
            $params['message'] = $status->getMessage();

            try {
                $response = $connection->api('/' . $status->getFacebookLocation()->getIdentifier() . '/feed', 'POST', $params);
            } catch (\Exception $e) {
                throw new ExternalApiException($e->getMessage(), $e->getCode(), $e);
            }
        }

        $connection->destroySession();

        // Set URL to published status message on Facebook
        $statusURL = 'https://www.facebook.com/'.str_replace('_', '/posts/', $response['id']);

        $status->setUrl($statusURL);
        $status->setPostId($response['id']);
        // Set Operation to closed.
        $status->getOperation()->setStatus(Action::STATUS_CLOSED);

        $location = $status->getOperation()->getLocations()[0];
        $location->setIdentifier($response['id']);
        $location->setUrl($statusURL);
        $location->setName($status->getOperation()->getName());
        $location->setStatus(Medium::STATUS_ACTIVE);

        // Schedule data collection for report
        $this->reportPublishStatus->schedule($status->getOperation());

        $this->em->flush();

        $this->message = 'The message "'.$params['message'].'" with the ID "'.$response['id'].'" has been posted on Facebook';
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