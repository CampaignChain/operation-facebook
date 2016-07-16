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

use CampaignChain\CoreBundle\Entity\SchedulerReportOperation;
use CampaignChain\CoreBundle\Job\JobReportInterface;
use Doctrine\ORM\EntityManager;

class ReportPublishStatus implements JobReportInterface
{
    const BUNDLE_NAME = 'campaignchain/operation-facebook';
    const METRIC_LIKES = 'Likes';
    const METRIC_SHARES = 'Shares';
    const METRIC_COMMENTS = 'Comments';

    protected $em;
    protected $container;

    protected $message;

    protected $status;

    public function __construct(EntityManager $em, $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function schedule($operation, $facts = null)
    {
        $scheduler = new SchedulerReportOperation();
        $scheduler->setOperation($operation);
        $scheduler->setInterval('1 hour');
        $scheduler->setEndAction($operation->getActivity()->getCampaign());
        $this->em->persist($scheduler);

        // Add initial data to report.
        $this->status = $this->em->getRepository('CampaignChainOperationFacebookBundle:StatusBase')->findOneByOperation($operation);
        if (!$this->status) {
            throw new \Exception('No Facebook status found for an operation with ID: '.$operation->getId());
        }

        $facts[self::METRIC_LIKES] = 0;
//        $facts[self::METRIC_SHARES] = 0;
        $facts[self::METRIC_COMMENTS] = 0;

        $factService = $this->container->get('campaignchain.core.fact');
        $factService->addFacts('activity', self::BUNDLE_NAME, $operation, $facts);
    }

    public function execute($operationId)
    {
        $this->status = $this->em->getRepository('CampaignChainOperationFacebookBundle:StatusBase')->findOneByOperation($operationId);
        if (!$this->status) {
            throw new \Exception('No Facebook status found for an operation with ID: '.$operationId);
        }

        $channel = $this->container->get('campaignchain.channel.facebook.rest.client');
        $connection = $channel->connectByActivity($this->status->getOperation()->getActivity());

        if ($connection) {
            $params["summary"] = "1";
            $response = $connection->api('/'.$this->status->getPostId().'/likes', 'GET', $params);
            $likes = $response['summary']['total_count'];

            $params["summary"] = "1";
            $params["filter"] = "stream";
            $response = $connection->api('/'.$this->status->getPostId().'/comments', 'GET', $params);
            $comments = $response['summary']['total_count'];

//            $response = $connection->api('/'.$this->status->getPostId().'/sharedposts?fields=from,via', 'GET', $params);

            $connection->destroySession();
        }

        // Add report data.
        $facts[self::METRIC_LIKES] = $likes;
//        $facts[self::METRIC_SHARES] = $shares;
        $facts[self::METRIC_COMMENTS] = $comments;

        $factService = $this->container->get('campaignchain.core.fact');
        $factService->addFacts('activity', self::BUNDLE_NAME, $this->status->getOperation(), $facts);

        $this->message = 'Added to report: likes = '.$likes.', comments = '.$comments.'.';

        return self::STATUS_OK;
    }

    public function getMessage(){
        return $this->message;
    }
}