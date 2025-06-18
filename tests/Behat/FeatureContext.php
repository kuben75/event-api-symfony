<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Mink;
use Behat\MinkExtension\Context\MinkAwareContext;
use Behat\Step\Given;
use Behat\Step\When;
use Behat\Step\Then;
use Webmozart\Assert\Assert;

class FeatureContext implements Context, MinkAwareContext
{
    private Mink $mink;
    private array $placeholders = [];
    private ?PyStringNode $requestBody = null;

    public function setMink(Mink $mink): void
    {
        $this->mink = $mink;
    }

    public function setMinkParameters(array $parameters): void
    {
    }

    /** @BeforeScenario */
    public function reset(): void
    {
        $this->placeholders = [];
        $this->requestBody = null;
    }

    #[Given('I am authenticated as :email with password :password')]
    public function iAmAuthenticatedAs(string $email, string $password): void
    {
        $this->iSendARequestTo('POST', '/api/login_check', new PyStringNode([json_encode([
            'email' => $email,
            'password' => $password,
        ])], 0));

        $data = json_decode($this->mink->getSession()->getPage()->getContent(), true);
        Assert::keyExists($data, 'token', 'Login failed, could not get token.');
        $this->placeholders['{token}'] = $data['token'];
    }

    #[Given('I set the request body to:')]
    public function iSetTheRequestBodyTo(PyStringNode $body): void
    {
        $this->requestBody = $body;
    }

    #[Given('I send a :method request to :path with body:')]
    public function iSendARequestToWithBody(string $method, string $path, PyStringNode $body): void
    {
        $this->iSendARequestTo($method, $path, $body);
    }

    #[Given('I store the value of JSON node :path as :name')]
    public function iStoreTheValueFromTheResponseJsonNodeAs(string $path, string $name): void
    {
        $responseJson = json_decode($this->mink->getSession()->getPage()->getContent(), true);
        $keys = explode('.', $path);
        $value = $responseJson;
        foreach ($keys as $key) {
            Assert::keyExists($value, $key, sprintf('Key "%s" not found in JSON node path.', $key));
            $value = $value[$key];
        }
        $this->placeholders['{' . $name . '}'] = $value;
    }

    #[When('I send a :method request to :path')]
    public function iSendARequestTo(string $method, string $path, ?PyStringNode $body = null): void
    {
        $path = str_replace(array_keys($this->placeholders), array_values($this->placeholders), $path);

        $content = $body?->getRaw() ?? $this->requestBody?->getRaw();

        $serverParameters = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if (isset($this->placeholders['{token}'])) {
            $serverParameters['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->placeholders['{token}'];
        }

        $this->mink->getSession()->getDriver()->getClient()->request($method, $path, [], [], $serverParameters, $content);
    }

    #[Then('the response code should be :code')]
    public function theResponseCodeShouldBe(int $code): void
    {
        $realStatusCode = $this->mink->getSession()->getStatusCode();
        if ($realStatusCode !== $code) {
            $responseBody = $this->mink->getSession()->getPage()->getContent();
            $message = sprintf('Current response status code is %d, but %d expected. Response body: %s', $realStatusCode, $code, $responseBody);
            throw new \RuntimeException($message);
        }
    }

    #[Then('the response should contain :text')]
    public function theResponseShouldContain(string $text): void
    {
        Assert::contains($this->mink->getSession()->getPage()->getContent(), $text);
    }

    #[Then('the JSON node :path should be equal to :expectedValue')]
    public function theJsonNodeShouldBeEqualTo(string $path, string $expectedValue): void
    {
        $responseJson = json_decode($this->mink->getSession()->getPage()->getContent(), true);
        $keys = explode('.', $path);
        $actualValue = $responseJson;
        foreach ($keys as $key) {
            Assert::keyExists($actualValue, $key, sprintf('Key "%s" not found in JSON node path.', $key));
            $actualValue = $actualValue[$key];
        }
        Assert::eq((string) $actualValue, $expectedValue, "JSON node '{$path}' has value '{$actualValue}', but '{$expectedValue}' was expected.");
    }
}
