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
    public function loadData(ObjectManager $manager)
    {
        $this->createMany(Thread::class, 150, function (Thread $thread) use ($manager) {
            $thread->setTitle($this->faker->words(rand(4, 8), true))
                ->setAuthor($this->getRandomReference(User::class))
                ->setCreatedAt($this->faker->dateTimeBetween('-1 years'))
                ->setForum($this->getRandomReference(Forum::class));

            $this->faker->boolean(40) ? $thread->setLocked(true) : $thread->setLocked(false);

            $message = new Message();
            $message->setAuthor($thread->getAuthor())
                ->setPublishedAt($thread->getCreatedAt())
                ->setContent($this->faker->sentences(mt_rand(1, 15), true))
                ->setThread($thread);

            $this->faker->boolean() ? $message->setUpdatedAt($this->faker->dateTimeBetween($message->getPublishedAt())) : $message->setUpdatedAt(null);

            $manager->persist($message);
        });

        $manager->flush();
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            ForumFixtures::class,
            UserFixtures::class
        ];
    }
}