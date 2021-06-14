<?php
namespace Twikey\Api\Helper;

/**
 * Interface MandateCallback See SampleMandateCallback in the tests for a sample implementation
 * @package Twikey\Api\Helper
 */
interface MandateCallback
{
    public function handleNew($data);
    public function handleUpdate($data);
    public function handleCancel($data);
}
