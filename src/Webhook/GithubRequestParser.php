<?php

namespace App\Webhook;

use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class GithubRequestParser extends AbstractRequestParser
{
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            new MethodRequestMatcher(Request::METHOD_POST),
            new IsJsonRequestMatcher(),
        ]);
    }


    protected function doParse(
        Request $request,
        #[\SensitiveParameter]
        string $secret
    ): RemoteEvent {
        $name = $request->headers->get('X-GitHub-Event');
        $id = $request->headers->get('X-GitHub-Hook-ID');

        if (null === $name || null === $id) {
            throw new RejectWebhookException(400, 'Missing required GitHub headers.');
        }

        return new RemoteEvent(
            name: $name,
            id: $id,
            payload: $request->getPayload()->all()
        );
    }
}
