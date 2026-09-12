<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Attributes\Audience as AudienceAttribute;
use Codewiser\Postie\Attributes\Channel as ChannelAttribute;
use Codewiser\Postie\Attributes\Channels as ChannelsAttribute;
use Codewiser\Postie\Attributes\Description;
use Codewiser\Postie\Attributes\Group as GroupAttribute;
use Codewiser\Postie\Attributes\Groups as GroupsAttribute;
use Codewiser\Postie\Attributes\Preview;
use Codewiser\Postie\Attributes\Subject;
use Codewiser\Postie\Collections\Groups;
use Codewiser\Postie\Traits\HasAudience;
use Codewiser\Postie\Traits\HasChannels;
use Codewiser\Postie\Traits\HasTitle;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;

class Subscription implements Arrayable
{
    use HasChannels, HasAudience, HasTitle, Conditionable;

    protected ?string $description = null;
    /**
     * @var null|callable
     */
    protected $preview = null;
    /**
     * @var null|string  Name of a static method, marked with the Preview attribute.
     */
    protected ?string $previewMethod = null;
    /**
     * @var array<int, Group>
     */
    protected array $groups = [];
    /**
     * Is the subscription auto-discovered via notification attributes?
     */
    protected bool $discovered = false;

    /**
     * Make subscription definition using notification class name.
     *
     * @param  class-string<Notification>  $notification
     */
    public static function to(string $notification): static
    {
        return new static($notification);
    }

    /**
     * @param  class-string<Notification>  $notification  Notification class name.
     */
    public function __construct(protected string $notification)
    {
        // Define subscription defaults using attributes applied to the notification class
        $reflection = new \ReflectionClass($notification);

        // Title
        $attribute = $reflection->getAttributes(Subject::class);

        $this->title = $attribute
            ? $attribute[0]->newInstance()->title
            : (string) Str::of(class_basename($notification))->snake()->studly();

        // Description
        $attribute = $reflection->getAttributes(Description::class);

        if ($attribute) {
            $this->description = $attribute[0]->newInstance()->description;
        }

        // Channels
        foreach ($reflection->getAttributes(ChannelAttribute::class) as $attribute) {
            $this->channels[] = $attribute->newInstance()->toChannel();
        }

        foreach ($reflection->getAttributes(ChannelsAttribute::class) as $attribute) {
            foreach ($attribute->newInstance()->toChannels() as $channel) {
                $this->channels[] = $channel;
            }
        }

        // Groups
        foreach ($reflection->getAttributes(GroupAttribute::class) as $attribute) {
            $this->attachGroup($this->resolveGroup($attribute->newInstance()->name));
        }

        foreach ($reflection->getAttributes(GroupsAttribute::class) as $attribute) {
            foreach ($attribute->newInstance()->names as $name) {
                $this->attachGroup($this->resolveGroup($name));
            }
        }

        // Preview
        foreach ($reflection->getMethods(\ReflectionMethod::IS_STATIC) as $method) {
            if ($method->getAttributes(Preview::class)) {
                $this->previewMethod = $method->getName();

                break;
            }
        }

        // Audience
        $attribute = $reflection->getAttributes(AudienceAttribute::class);

        if ($attribute) {
            $audience = $attribute[0]->newInstance()->toAudience();

            if ($audience->hasBuilder()) {
                $this->for($audience);
            }
        }
    }

    /**
     * Put subscription to a group.
     *
     * A group referenced by its name inherits icon, weight and other properties
     * from a predefined group definition (see PostieService::$groups).
     */
    public function group(Group|string $group): static
    {
        $this->attachGroup(
            $group instanceof Group ? $group : $this->resolveGroup($group)
        );

        return $this;
    }

    /**
     * Attach a group to the subscription.
     *
     * Subscriptions inherit channels and audience from a group,
     * unless they define their own.
     */
    protected function attachGroup(Group $group): Group
    {
        if ($this->getChannels()->isEmpty() && $group->getChannels()->isNotEmpty()) {
            $this->via($group->getChannels()->all());
        }

        if (! $this->isOwnAudience()
            && ! $this->getAudience()
            && ($group->getAudience()?->hasBuilder() ?? false)) {
            $this->inheritAudience($group->getAudience());
        }

        $this->groups[] = $group;

        return $group;
    }

    /**
     * Get a predefined group by its title, or a bare group otherwise.
     */
    protected function resolveGroup(string $group): Group
    {
        $defined = $this->getService()
            ->getGroups()
            ->first(fn(Group $definition) => $definition->getTitle() === $group);

        return $defined ? clone $defined : new Group($group);
    }

    /**
     * Set subscription human-readable description.
     */
    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set notification preview.
     *
     * Callback will get 'channel' and 'notifiable' parameters and should return any renderable content.
     *
     * @param  callable(string, object): mixed  $preview
     */
    public function preview(callable $preview): static
    {
        $this->preview = $preview;

        return $this;
    }

    /**
     * Check if notification has a preview.
     */
    public function hasPreview(Channel $channel, object $notifiable): bool
    {
        return (bool) $this->getPreview($channel, $notifiable);
    }

    /**
     * Get subscription groups.
     */
    public function getGroups(): Groups
    {
        return new Groups($this->groups ?: [Group::fallback()]);
    }

    /**
     * Mark subscription as auto-discovered via notification attributes.
     */
    public function discovered(bool $discovered = true): static
    {
        $this->discovered = $discovered;

        return $this;
    }

    /**
     * Is the subscription auto-discovered via notification attributes?
     */
    public function isDiscovered(): bool
    {
        return $this->discovered;
    }

    /**
     * Get notification class name.
     *
     * @return class-string<Notification>
     */
    public function getNotification(): string
    {
        return $this->notification;
    }

    /**
     * Get subscription description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get notification preview.
     */
    public function getPreview(string|Channel $channel, object $notifiable): mixed
    {
        if ($channel instanceof Channel) {
            $channel = $channel->getName();
        }

        return is_callable($this->preview)
            ? call_user_func($this->preview, $channel, $notifiable)
            : $this->callAttributePreview($channel, $notifiable);
    }

    /**
     * Call a static method, marked with the Preview attribute, to get the preview.
     *
     * Such method should return a callable.
     */
    protected function callAttributePreview(string $channel, object $notifiable): mixed
    {
        if (! $this->previewMethod) {
            return null;
        }

        $preview = call_user_func([$this->notification, $this->previewMethod]);

        return is_callable($preview) ? call_user_func($preview, $channel, $notifiable) : null;
    }

    /**
     * Get titles of audiences the subscription is scoped to.
     *
     * An own audience (set via `for()`) overrides and hides group audiences.
     * Otherwise, audiences of every attached group are listed.
     *
     * @return array<int, string>
     */
    public function getAudienceTitles(): array
    {
        if ($this->isOwnAudience()) {
            return $this->getAudience() ? [$this->getAudience()->getTitle()] : [];
        }

        $titles = [];

        foreach ($this->getGroups() as $group) {
            if ($group->getAudience()) {
                $titles[$group->getAudience()->getName()] ??= $group->getAudience()->getTitle();
            }
        }

        return array_values($titles);
    }

    public function toArray(): array
    {
        return [
            'groups'       => $this->getGroups(),
            'notification' => $this->getNotification(),
            'title'        => $this->getTitle(),
            'description'  => $this->getDescription(),
            'audiences'    => $this->getAudienceTitles(),
            'channels'     => $this->getChannels()->toArray(),
            //'preview'      => $this->hasPreview(),
        ];
    }
}
