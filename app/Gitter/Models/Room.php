<?php
namespace App\Gitter\Models;
use App\Gitter\Client;
use Carbon\Carbon;

/**
 * Class Room
 * @package App\Gitter\Models
 *
 * === Attributes ===
 *
 * @property-read string $id
 * @property-read string $name
 * @property-read string $topic
 * @property-read string $uri
 * @property-read bool $oneToOne
 * @property-read int $userCount
 * @property-read int $unreadItems
 * @property-read int $mentions
 * @property-read string $lastAccessTime
 * @property-read bool $lurk
 * @property-read bool $activity
 * @property-read string $url
 * @property-read string $githubType
 * @property-read string $security
 * @property-read bool $noindex
 * @property-read array $tags
 * @property-read bool $roomMember
 *
 * === Relations ===
 *
 * @property-read \Generator|User[] $users
 * @property-read MessagesChunk|Message[] $messages
 *
 */
class Room extends AbstractModel
{
    public function __construct(Client $client, \stdClass $data)
    {
        parent::__construct($client, $data);
    }

    /**
     * @param bool $async
     * @return \Generator|User[]
     */
    public function users(bool $async = false)
    {
        $result = $this->client
            ->request('room.users')
            ->where('roomId', $this->id)
            ->then(function($users) {
                foreach ($users as $user) {
                    yield new User($this->client, $user);
                }
            });

        return $async ? $result : $result->wait();
    }

    /**
     * @param bool $async
     * @param callable $query
     * @return MessagesChunk|Message[]
     */
    public function messages(bool $async = false, callable $query = null)
    {
        $chunk = new MessagesChunk($this->client, $this);
        return $chunk->query($query, $async);
    }

    /**
     * @param string $message
     * @param bool $async
     * @return mixed|Message
     */
    public function say(string $message, bool $async = false)
    {
        $result = $this->client
            ->request('message.send')
            ->where('roomId', $this->id)
            ->fetch([
                'method' => 'POST',
                'body'   => ['text' => $message]
            ])
            ->then(function($message) {
                return new Message($this->client, $this, $message);
            });

        return $async ? $result : $result->wait();
    }
}
