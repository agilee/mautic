<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Interface MauticController
 * A dummy interface to ensure that only Mautic bundles are affected by Mautic onKernelController events
 *
 * @package Mautic\CoreBundle\Controller
 */

interface MauticController
{
    public function initialize(FilterControllerEvent $event);
}