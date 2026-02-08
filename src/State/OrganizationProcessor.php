<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Organization;
use App\Entity\OrganizationMember;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class OrganizationProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Organization {
        if (!$data instanceof Organization) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('User not authenticated');
        }

        // Si c'est une création (POST), ajouter le créateur comme admin
        if ($operation instanceof Post) {
            $data->setCreatedBy($user);

            $member = new OrganizationMember();
            $member->setUser($user);
            $member->setOrganization($data);
            $member->setRole(OrganizationMember::ROLE_ADMIN);
            $member->setJoinedAt(new \DateTimeImmutable());

            $data->addMember($member);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
