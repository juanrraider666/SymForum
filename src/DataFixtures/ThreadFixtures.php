<?php

namespace App\DataFixtures;

use App\Entity\Forum;
use App\Entity\Message;
use App\Entity\Thread;
use App\Entity\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class ThreadFixtures extends BaseFixtures implements DependentFixtureInterface
{
    /** @var Thread[] $threads */
    private array $threads = [];

    public function loadData(ObjectManager $manager): void
    {
        $this->createMany(Thread::class, 150, function (Thread $thread) {
            /** @var Forum $forum */
            $forum = $this->getRandomReference(Forum::class);

            /** @var User $author */
            $author = $this->getRandomReference(User::class);

            $thread->setTitle($this->faker->words(rand(4, 8), true))
                ->setAuthor($author)
                ->setCreatedAt($this->faker->dateTimeBetween('-1 years'))
                ->setForum($forum);

            $this->faker->boolean(40) ? $thread->setIsLock(true) : $thread->setIsLock(false);
            $this->faker->boolean(10) ? $thread->setIsPin(true) : $thread->setIsPin(false);

            $forum->incrementTotalThreads();
            $this->threads[] = $thread;
        });

        foreach($this->threads as $thread) {
            $firstMessage = new Message();
            $firstMessage->setAuthor($thread->getAuthor())
                ->setPublishedAt($thread->getCreatedAt())
                ->setContent($this->faker->sentences(mt_rand(1, 15), true))
                ->setThread($thread);

            $this->faker->boolean() ? $firstMessage->setUpdatedAt($this->faker->dateTimeBetween($firstMessage->getPublishedAt())) : $firstMessage->setUpdatedAt(null);

            $manager->persist($firstMessage);

            $thread->incrementTotalMessages();
            $thread->setLastMessage($firstMessage);

            $thread->getForum()->incrementTotalMessages();
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ForumFixtures::class,
            UserFixtures::class
        ];
    }
}
