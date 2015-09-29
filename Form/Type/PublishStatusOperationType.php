<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\FacebookBundle\Form\Type;

use CampaignChain\CoreBundle\Form\Type\OperationType;
use CampaignChain\Operation\FacebookBundle\Entity\UserStatus;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PublishStatusOperationType extends OperationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('message', 'textarea', array(
                'label' => false,
                'attr' => array(
                    'placeholder' => 'Compose message...',
                    'maxlength' => 2000,
                ),
            ));

        if($this->operationDetail instanceof UserStatus){
            $builder
                ->add('privacy', 'choice', array(
                    'label' => 'Audience',
                    'choices'   => array(
                        'EVERYONE' => 'Public',
                        'ALL_FRIENDS' => 'Friends',
                        'FRIENDS_OF_FRIENDS' => 'Friends of Friends',
                        'SELF' => 'Only Me'
                    ),
                    'multiple'  => false,
                ));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $defaults = array(
            'data_class' => get_class($this->operationDetail),
        );

        if($this->operationDetail){
            $defaults['data'] = $this->operationDetail;
        }
        $resolver->setDefaults($defaults);
    }

    public function getName()
    {
        return 'campaignchain_operation_facebook_publish_status';
    }
}