<?php

namespace Uniform\Tests\Actions;

use StdClass;
use Exception;
use Uniform\Form;
use Uniform\Tests\TestCase;
use Uniform\Actions\WebhookAction;
use Uniform\Exceptions\PerformerException;

class WebhookActionTest extends TestCase
{
    protected $form;
    public function setUp()
    {
        parent::setUp();
        $this->form = new Form;
    }

    public function testFileOptionRequired()
    {
        $action = new WebhookActionStub($this->form);
        $this->setExpectedException(Exception::class);
        $action->perform();
    }

    public function testPerform()
    {
        $this->form->data('message', 'my message');
        $action = new WebhookActionStub($this->form, [
            'url' => 'example.com',
            'params' => [
                'data' => ['key' => 123],
                'headers' => ['X-Auth: ABC']
            ]
        ]);
        $action->perform();
        $this->assertEquals('example.com', $action->url);
        $expect = ['message' => 'my message', 'key' => 123];
        $this->assertEquals($expect, $action->params['data']);
        $expect = ['X-Auth: ABC', 'Content-Type: application/x-www-form-urlencoded'];
        $this->assertEquals($expect, $action->params['headers']);
    }

    public function testPerformJson()
    {
        $this->form->data('message', 'my message');
        $action = new WebhookActionStub($this->form, [
            'url' => 'example.com',
            'json' => true,
        ]);
        $action->perform();
        $this->assertEquals('{"message":"my message"}', $action->params['data']);
        $expect = ['Content-Type: application/json'];
        $this->assertEquals($expect, $action->params['headers']);
    }

    public function testPerformOnly()
    {
        $this->form->data('message', 'my message');
        $this->form->data('name', 'joe');
        $this->form->data('password', 'secret');
        $action = new WebhookActionStub($this->form, [
            'url' => 'example.com',
            'only' => ['name', 'password'],
            'except' => ['password'],
        ]);
        $action->perform();
        $this->assertEquals(['name' => 'joe'], $action->params['data']);
    }

    public function testFail()
    {
        $action = new WebhookActionStub($this->form, ['url' => 'example.com']);
        $action->shouldFail = true;
        $this->setExpectedException(PerformerException::class);
        $action->perform();
    }
}

class WebhookActionStub extends WebhookAction
{
    protected function request($url, $params)
    {
        $this->url = $url;
        $this->params = $params;
        $response = new StdClass;
        $response->error = isset($this->shouldFail) ? 1 : 0;
        $response->message = '';
        return $response;
    }
}
