<?php


namespace App\Messenger\Messages;


class FeedbackStatusEvent
{
    private int $testDriveId;

    public function __construct(int $testDriveId)
    {
        $this->testDriveId = $testDriveId;
    }

    public function getTestDriveId(): int
    {
        return $this->testDriveId;
    }
}