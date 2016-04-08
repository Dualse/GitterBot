<?php
/**
 * This file is part of GitterBot package.
 *
 * @author Serafim <nesk@xakep.ru>
 * @date 11.10.2015 8:27
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Interfaces\Gitter\Subscriber;

/**
 * Interface SubscriberInterface
 */
interface SubscriberInterface
{
    /**
     * @return mixed
     */
    public function handle();
}