<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\FacebookBundle\EntityService;

use Doctrine\ORM\EntityManager;

class Status
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getStatusByOperation($id){
        $status = $this->em->getRepository('CampaignChainOperationFacebookBundle:StatusBase')
            ->findOneByOperation($id);

        if (!$status) {
            // TODO: Exception needs to be used.
            throw new \Exception(
                'No status found by operation id '.$id
            );
        }

        return $status;
    }
}