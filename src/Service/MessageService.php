<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\Thread;
use App\Entity\User;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Security;

class MessageService
{
    private EntityManagerInterface $em;

    private MessageRepository $messageRepository;

    private FlashBagInterface $flashBag;

    private AntispamService $antispamService;

    private Security $security;

    public function __construct(EntityManagerInterface $em, MessageRepository $messageRepository, FlashBagInterface $flashBag, AntispamService $antispamService, Security $security)
    {
        $this->em = $em;
        $this->messageRepository = $messageRepository;
        $this->flashBag = $flashBag;
        $this->antispamService = $antispamService;
        $this->security = $security;
    }

    /**
     * @param Thread $thread
     * @param User $user
     * @return bool
     */
    public function canPostMessage(Thread $thread, User $user): bool
    {
        if ($thread->isLock()) {
            $this->flashBag->add('error', ['title' => 'Message', 'content' => 'Vous ne pouvez pas ajouter votre message, le sujet est verrouillé !']);
            return false;
        } elseif (!$this->antispamService->canPostMessage($user)) {
            $this->flashBag->add('error', ['title' => 'Message', 'content' => 'Vous devez encore attendre un peu avant de pouvoir poster un message !']);
            return false;
        }

        return true;
    }

    /**
     * @param Message $message
     * @return bool
     */
    public function canEditMessage(Message $message): bool
    {
        if($this->security->isGranted('ROLE_MODERATOR')) {
            return true;
        }

        if ($message->getThread()->isLock()) {
            $this->flashBag->add('error', ['title' => 'Message', 'content' => 'Vous ne pouvez pas éditer votre message, le sujet est verrouillé !']);
            return false;
        }

        return true;
    }

    /**
     * @param Message $message
     * @return bool
     */
    public function canDeleteMessage(Message $message): bool
    {
        $thread = $message->getThread();
        $firstMessageInThread = $this->messageRepository->findFirstMessageInThread($thread);

        if ($message === $firstMessageInThread && $thread->getTotalMessages() > 1) {
            $this->flashBag->add('error', ['title' => 'Message', 'content' => 'Le premier message ne peut pas être supprimé car le sujet contient des réponses !']);

            return false;
        }

        return true;
    }

    /**
     * @param string $content
     * @param Thread $thread
     * @param User $user
     * @return Message
     */
    public function createMessage(string $content, Thread $thread, User $user): Message
    {
        $message = (new Message())
            ->setAuthor($user)
            ->setContent($content)
            ->setThread($thread);

        $this->em->persist($message);

        $thread->setLastMessage($message);
        $thread->getForum()->setLastMessage($message);
        $thread->incrementTotalMessages();

        $forum = $thread->getForum();
        $forum->incrementTotalMessages();

        $this->em->flush();

        return $message;
    }

    /**
     * @param Message $message
     * @return Message|null
     */
    public function deleteMessage(Message $message): ?Message
    {
        $thread = $message->getThread();
        $forum = $thread->getForum();

        if ($thread->getLastMessage() === $message) {
            $thread->setLastMessage(null);
        }

        if ($forum->getLastMessage() === $message) {
            $forum->setLastMessage(null);
        }

        $this->em->remove($message);
        $thread->decrementTotalMessages();

        $forum = $thread->getForum();
        $forum->decrementTotalMessages();

        $this->em->flush();

        if (!$forum->getLastMessage()) {
            $forum->setLastMessage($this->messageRepository->findLastMessageByForum($forum));
        }

        return $this->messageRepository->findLastMessageByThread($thread);
    }

    /**
     * @param User $user
     * @return void
     */
    public function deleteMessagesByUser(User $user): void
    {
        foreach ($user->getMessages() as $message) {
            $this->deleteMessage($message);
        }
    }

    /**
     * @param User $user
     * @return void
     */
    public function setAuthorNullByUser(User $user): void
    {
        if (count($user->getMessages()) > 0) {
            foreach ($user->getMessages() as $message) {
                $message->setAuthor(null);
                $message->setContent('supprimé');
            }

            $this->em->flush();
        }
    }
}
