<?php

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

abstract class AbstractFixture extends Fixture
{
    /**
     * instance de $faker qui sera dispo dans toutes nos fixtures
     */
    protected Generator $faker;

    /**
     * instance de l'Object Manager qui sera dispo dans toutes nos fixtures
     */
    protected ObjectManager $manager;

    /**
     * Tableau pour stocker le nombre d'instances créées par classe
     */
    private array $referenceCounts = [];

    /**
     * Function Abstraite qui sera appelée après la function load 
     */
    abstract protected function loadData(ObjectManager $manager): void;

    /**
     * Initialisation de la Fixture
     */
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->faker = Factory::create('fr_FR');
        $this->customizeFaker();
        $this->loadData($manager);
    }

    /**
     * fonction qui renvoie une référence à une entité créée dans une autre fixture auparavant 
     * en précisant la class de l'entité que vous recherchez
     */
    protected function getRandomReference(string $classname): object
    {
        if (!isset($this->referenceCounts[$classname]) || $this->referenceCounts[$classname] === 0) {
            throw new \Exception("Pas de références pour $classname");
        }

        $randomIndex = $this->faker->numberBetween(0, $this->referenceCounts[$classname] - 1);
        $referenceName = $classname . '_' . $randomIndex;

        return $this->getReference($referenceName, $classname);
    }

    /**
     * fonction qui permet d'automatiser la création de fixture en lui injectant
     * le nom de la classe, le nombre d'occurrences et un callable qui représente les paramètres de remplissage de la table
     */
    public function createMany(string $classname, int $count, callable $callback): void
    {
        for ($i = 0; $i < $count; $i++) {
            $obj = new $classname;
            $callback($obj, $i);
            $this->manager->persist($obj);
            $this->addReference($classname . '_' . $i, $obj);
        }

        // Stocker le nombre d'instances créées
        $this->referenceCounts[$classname] = $count;

        $this->manager->flush();
    }

    /**
     * Méthode optionnelle pour personnaliser Faker
     */
    protected function customizeFaker(): void
    {
        // À surcharger dans les fixtures enfants si nécessaire
    }

    /**
     * Retourne toutes les références créées pour une classe donnée
     *
     * @param string $classname
     * @return array<object>
     */
    protected function getAllReferences(string $classname): array
    {
        $count = $this->referenceCounts[$classname] ?? 0;
        $refs = [];

        for ($i = 0; $i < $count; $i++) {
            $referenceName = $classname . '_' . $i;

            // Ta méthode getReference() demande 2 arguments
            $refs[] = $this->getReference($referenceName, $classname);
        }

        return $refs;
    }


}