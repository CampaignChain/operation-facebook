<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\FacebookBundle\Job;

use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\CTA;
use CampaignChain\CoreBundle\Entity\Medium;
use CampaignChain\Operation\FacebookBundle\Entity\UserStatus;
use Doctrine\ORM\EntityManager;
use CampaignChain\CoreBundle\Job\JobServiceInterface;
use CampaignChain\CoreBundle\Util\ParserUtil;

class PublishStatus implements JobServiceInterface
{
    protected $em;
    protected $container;

    protected $message;

    public function __construct(EntityManager $em, $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function execute($operationId)
    {
        $status = $this->em->getRepository('CampaignChainOperationFacebookBundle:StatusBase')->findOneByOperation($operationId);

        if (!$status) {
            throw new \Exception('No Facebook status found for an operation with ID: '.$operationId);
        }

        // Process URLs in message and save the new message text, now including
        // the replaced URLs with the Tracking ID attached for call to action tracking.
        $ctaService = $this->container->get('campaignchain.core.cta');
        $status->setMessage(
            $ctaService->processCTAs($status->getMessage(), $status->getOperation(), 'txt')->getContent()
        );

        $channel = $this->container->get('campaignchain.channel.facebook.rest.client');
        $connection = $channel->connectByActivity($status->getOperation()->getActivity());

        if ($connection) {
            $params = array();

            if($status instanceof UserStatus){
                $privacy = array(
                    'value' => $status->getPrivacy()
                );
                $params['privacy'] = json_encode($privacy);
            }
            $params['message'] = $status->getMessage();
            $response = $connection->api('/'.$status->getFacebookLocation()->getIdentifier().'/feed', 'POST', $params);

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

            $this->em->flush();

            $this->message = 'The message "'.$params['message'].'" with the ID "'.$response['id'].'" has been posted on Facebook';
            if($status instanceof UserStatus){
                $this->message .= ' with privacy setting "'.$privacy['value'].'"';
            }
            $this->message .= '. See it on Facebook: <a href="'.$statusURL.'">'.$statusURL.'</a>';

            return self::STATUS_OK;
        }

        //die('End');
//        $client = new Client();
//        $oauth = new OauthPlugin(array(
//            'consumer_key'    => 'xxx',
//            'consumer_secret' => 'xxx'
//        ));
//        $client->addSubscriber($oauth);
//
//        try {
//            $request = $this->baseURL.'/me/feed?privacy=SELF&access_token='.$this->accessToken;
//            $response = $client->get($request)->send();
//            print_r($response->json());die();
//        //$response = $client->get('me')->send()->getBody();
//        } catch (ClientErrorResponseException $exception) {
//            $responseBody = $exception->getResponse()->getBody(true);
//            print_r($responseBody);die();
//        }
    }

    public function getMessage()
    {
        return $this->message;
    }
}