<?php

namespace Oro\Bundle\EmailBundle\Migrations\DataFixtures\ORM\v1_0;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;

class LoadInternalEmailOrigins extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $outboxFolder = new EmailFolder();
        $outboxFolder
            ->setType(EmailFolder::SENT)
            ->setName(EmailFolder::SENT)
            ->setFullName(EmailFolder::SENT);

        $origin = new InternalEmailOrigin();
        $origin
            ->setName(InternalEmailOrigin::BAP)
            ->addFolder($outboxFolder);

        $manager->persist($origin);
        $manager->flush();
    }
}
