<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\FacebookBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class UserStatus extends StatusBase
{
    const PRIVACY_EVERYONE = 'EVERYONE';
    const PRIVACY_ALL_FRIENDS = 'ALL_FRIENDS';
    const PRIVACY_FRIENDS_OF_FRIENDS = 'FRIENDS_OF_FRIENDS';
    const PRIVACY_SELF = 'SELF';

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $privacy;

    /**
     * Set privacy
     *
     * @param string $privacy
     * @return UserStatus
     */
    public function setPrivacy($privacy)
    {
        if (!in_array($privacy, array(
            self::PRIVACY_ALL_FRIENDS,
            self::PRIVACY_EVERYONE,
            self::PRIVACY_FRIENDS_OF_FRIENDS,
            self::PRIVACY_SELF
        ))) {
            throw new \InvalidArgumentException("Invalid Facebook status privacy.");
        }
        $this->privacy = $privacy;

        return $this;
    }

    /**
     * Get privacy
     *
     * @return string
     */
    public function getPrivacy()
    {
        return $this->privacy;
    }
}
