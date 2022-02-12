<?php

namespace VolistxTeam\VSkeletonKernel\ValidationRules;

use Carbon\Carbon;
use GuzzleHttp\Client;
use VolistxTeam\VSkeletonKernel\Facades\Messages;
use VolistxTeam\VSkeletonKernel\Repositories\UserLogRepository;

class RequestsCountValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $sub_id = $this->inputs['token']->subscription()->first()->id;
        $plan = $this->inputs['plan'];
        $planRequestsLimit = $plan['data']['requests']?? null;

        if (config('log.adminLogMode') === 'local') {
            $repository = new UserLogRepository();
            $requestsMadeCount = $repository->FindLogsBySubscriptionCount($sub_id, Carbon::now());

        } else {
            $httpURL = config('log.userLogHttpUrl');
            $remoteToken = config('log.userLogHttpToken');
            $client = new Client();
            $response = $client->get("$httpURL/$sub_id/count", [
                'headers' => [
                    'Authorization' => "Bearer $remoteToken",
                ],
            ]);


            if (!$planRequestsLimit || ($planRequestsLimit != -1 && json_decode($response->getBody()) >= $planRequestsLimit)) {
                return [
                    'message' => Messages::E429(),
                    'code' => 429
                ];
            }
        }

        if ($response->getStatusCode() != 201) {
            //WE SEE WHAT WE DO
        }

        return true;
    }
}