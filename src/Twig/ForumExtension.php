<?php

namespace App\Twig;

use App\Entity\Category;
use App\Entity\Forum;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ForumExtension extends AbstractExtension
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * ForumExtension constructor.
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('root_category', [$this, 'getRootCategory']),
            new TwigFilter('breadcrumb', [$this, 'getBreadcrumbParts']),
        ];
    }

    public function getRootCategory(Forum $forum): Category
    {
        while (($parent = $forum->getParent()) !== null) {
            return $this->getRootCategory($parent);
        }

        return $forum->getCategory();
    }

    public function getBreadcrumbParts($forumOrCategory, array &$parts = []): array
    {
        if ($forumOrCategory instanceof Category) {
            $url = $this->urlGenerator->generate('forums.category', ['slug' => $forumOrCategory->getSlug()]);
            $title = $forumOrCategory->getTitle();
        } elseif ($forumOrCategory instanceof Forum) {
            $url = $this->urlGenerator->generate('forum.show', [
                'category_slug' => $this->getRootCategory($forumOrCategory)->getSlug(),
                'id' => $forumOrCategory->getId(),
                'slug' => $forumOrCategory->getSlug()
            ]);
            $title = $forumOrCategory->getTitle();
        } else {
            throw new \InvalidArgumentException('Filtered object must be an instance of Forum or Category.');
        }

        array_unshift($parts, ['url' => $url, 'title' => $title]);

        if ($forumOrCategory instanceof Forum && (($parent = $forumOrCategory->getParent()) || ($parent = $forumOrCategory->getCategory()))) {
            return $this->getBreadcrumbParts($parent, $parts);
        }

        return $parts;
    }
}