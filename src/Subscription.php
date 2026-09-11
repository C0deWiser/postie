<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Attributes\Channel as ChannelAttribute;
use Codewiser\Postie\Attributes\Description;
use Codewiser\Postie\Attributes\Group as GroupAttribute;
use Codewiser\Postie\Attributes\Preview;
use Codewiser\Postie\Attributes\Subject;
use Codewiser\Postie\Collections\Groups;
use Codewiser\Postie\Traits\HasAudience;
use Codewiser\Postie\Traits\HasChannels;
use Codewiser\Postie\Traits\HasTitle;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class Subscription implements Arrayable
{
    use HasChannels, HasAudience, HasTitle;

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
        $channels = $reflection->getAttributes(ChannelAttribute::class);

        if ($channels) {
            $this->channels = array_map(
                fn(\ReflectionAttribute $attribute) => $attribute->newInstance()->toChannel(),
                $channels
            );
        }

        // Groups
        $groups = $reflection->getAttributes(GroupAttribute::class);

        if ($groups) {
            $this->groups = array_map(
                fn(\ReflectionAttribute $attribute) => $this->attachGroup(
                    $this->resolveGroup($attribute->newInstance()->name)
                ),
                $groups
            );
        }

        // Preview
        foreach ($reflection->getMethods(\ReflectionMethod::IS_STATIC) as $method) {
            if ($method->getAttributes(Preview::class)) {
                $this->previewMethod = $method->getName();

                break;
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

        if (! $this->hasAudience() && $group->hasAudience()) {
            $this->for($group->getAudienceCallback());
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

    public function toArray(): array
    {
        return [
            'groups'       => $this->getGroups(),
            'notification' => $this->getNotification(),
            'title'        => $this->getTitle(),
            'description'  => $this->getDescription(),
            'channels'     => $this->getChannels()->toArray(),
            //'preview'      => $this->hasPreview(),
        ];
    }
}
