<?php

namespace Codewiser\Postie\Tests;

use Codewiser\Postie\Audience;
use Codewiser\Postie\Collections\Audiences;
use Codewiser\Postie\Collections\Subscriptions;
use Codewiser\Postie\PostieService;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\Fixtures\AudienceNotification;
use Codewiser\Postie\Tests\Fixtures\Audiences as AudiencesEnum;
use Codewiser\Postie\Tests\Fixtures\ScopedAudienceNotification;
use Codewiser\Postie\Tests\Models\User;

class AudienceTest extends TestCase
{
    private PostieService $postie;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postie = app(PostieService::class);

        $this->user = User::create([
            'name'     => 'John Doe',
            'email'    => 'john@doe.com',
            'password' => 'secret',
        ]);
    }

    public function test_audiences_are_materialized_from_provider(): void
    {
        $audiences = $this->postie->getAudiences();

        $this->assertInstanceOf(Audiences::class, $audiences);
        $this->assertSame(
            ['everyone', 'customers'],
            $audiences->map(fn(Audience $audience) => $audience->getName())->all()
        );
        $this->assertSame('Customers', $audiences->find('customers')->getTitle());
        $this->assertSame('Customers', $audiences->find(AudiencesEnum::Customers)->getTitle());
    }

    public function test_audience_supports_backed_enum_name(): void
    {
        $audience = Audience::make(AudiencesEnum::Customers, 'Customers');

        $this->assertSame('customers', $audience->getName());
        $this->assertSame('Customers', $audience->getTitle());
    }

    public function test_subscription_reads_audience_attribute_from_backed_enum(): void
    {
        $subscription = Subscription::to(AudienceNotification::class);

        $this->assertTrue($subscription->getAudience()->hasBuilder());
        $this->assertCount(1, (new Subscriptions([$subscription]))->for($this->user));
    }

    public function test_subscription_uses_predefined_audience_builder(): void
    {
        $subscription = Subscription::to(AudienceNotification::class);

        // Audience builder is taken from the predefined 'customers' definition.
        $this->assertSame(1, $subscription->getAudience()->getBuilder()->count());
    }

    public function test_subscription_throws_when_audience_is_not_defined(): void
    {
        $this->expectException(\RuntimeException::class);

        Subscription::to(ScopedAudienceNotification::class);
    }
}