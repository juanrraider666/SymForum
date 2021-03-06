<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Forum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class ForumFixtures extends BaseFixtures implements DependentFixtureInterface
{
    public function loadData(ObjectManager $manager): void
    {
        $this->createMany(Forum::class, 10, function (Forum $forum, $count) {
            /** @var Category $category */
            $category = $this->getRandomReference(Category::class);

            $forum->setTitle($this->faker->words(4, true))
                ->setDescription($this->faker->sentence)
                ->setCategory($category)
                ->setParent(null)
                ->setPosition($count);

            $this->faker->boolean(20) ? $forum->setIsLock(true) : $forum->setIsLock(false);
        });

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class
        ];
    }
}
