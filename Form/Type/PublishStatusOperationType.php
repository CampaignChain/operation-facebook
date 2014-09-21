<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\FacebookBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;

class PublishStatusOperationType extends AbstractType
{
    private $status = null;
    private $view = 'default';
    protected $em;
    protected $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function setStatus($status){
        $this->status = $status;
    }

    public function setView($view){
        $this->view = $view;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('message', 'textarea', array(
                'label' => false,
                'attr' => array(
                    'placeholder' => 'Compose message...',
                    'max_length' => 2000,
                ),
            ))
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

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $defaults = array(
            'data_class' => 'CampaignChain\Operation\FacebookBundle\Entity\Status',
        );

        if($this->status){
            $defaults['data'] = $this->status;
        }
        $resolver->setDefaults($defaults);
    }

    public function getName()
    {
        return 'campaignchain_operation_facebook_publish_status';
    }
}