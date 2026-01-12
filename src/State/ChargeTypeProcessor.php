<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\ChargeType;
use App\Entity\User;
use App\Service\OrganizationService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ChargeTypeProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private OrganizationService $organizationService
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): ChargeType {
        if (!$data instanceof ChargeType) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('User not authenticated');
        }

        $isUpdate = isset($context['previous_data']);

        // Toujours assigner le user
        if (!$isUpdate) {
            $data->setUser($user);
        }



        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
