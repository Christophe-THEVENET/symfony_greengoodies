<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager, ): void
    {
        // Pour charger les fixtures, utilisez la commande suivante
        // bien préciser le no intéraction pour éviter les erreurs CSRF
        //symfony console doctrine:fixtures:load --no-interaction

        $user1 = new User();
        $user1->setFirstname('John')
            ->setLastname('Doe')
            ->setEmail('johndoe@gmail.com')
            ->setRoles(['ROLE_USER'])
            ->setPassword($this->passwordHasher->hashPassword($user1, 'Azerty12!'));
        $manager->persist($user1);

        $user2 = new User();
        $user2->setFirstname('Jane')
            ->setLastname('Doe')
            ->setEmail('janedoe@gmail.com')
            ->setRoles(['ROLE_USER'])
            ->setPassword($this->passwordHasher->hashPassword($user2, 'Azerty12!'));
        $manager->persist($user2);

        $product1 = new Product();
        $product1->setName('Kit d\'hygiène recyclable');
        $product1->setShortDescription('Pour une salle de bain éco-friendly');
        $product1->setPrice(24.99);
        $product1->setImageFilename('hygiene.png');
        $product1->setLongDescription('Ce kit d’hygiène recyclable est conçu pour celles et ceux qui souhaitent allier bien-être et respect de l’environnement. Il contient l’essentiel pour une routine quotidienne complète, sans compromis sur la qualité ni sur la planète. Chaque élément du kit est fabriqué à partir de matériaux durables, recyclables ou biodégradables, afin de limiter au maximum l\'empreinte écologique. Que ce soit pour une utilisation personnelle, en voyage ou en cadeau responsable, ce kit est idéal pour amorcer une transition vers une consommation plus consciente. Il s\'inscrit parfaitement dans une démarche zéro déchet, en remplaçant les produits jetables par des alternatives durables et réutilisables.');
        $product1->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($product1);

        $product2 = new Product();
        $product2->setName('Shot Tropical');
        $product2->setShortDescription('Fruits frais, pressés à froid');
        $product2->setPrice(4.50);
        $product2->setImageFilename('shot_tropical.png');
        $product2->setLongDescription('Offrez-vous un concentré d’énergie naturelle avec notre shot tropical pressé à froid, élaboré à partir de fruits frais issus de l’agriculture responsable. Mangue, ananas, passion et citron vert sont pressés à froid pour préserver un maximum de nutriments sans recourir à la chaleur ni à aucun additif. Conditionné dans un flacon en verre recyclable, ce shot s’inscrit dans une démarche durable et zéro plastique. Idéal pour soutenir votre système immunitaire tout en réduisant votre impact environnemental, il allie plaisir gustatif et conscience écologique.');
        $product2->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($product2); 


        $product3 = new Product();
        $product3->setName('Gourde en bois');
        $product3->setShortDescription('50cl, bois d\'olivier');
        $product3->setPrice(16.90);
        $product3->setImageFilename('gourde.png');
        $product3->setLongDescription('Alliez élégance et conscience écologique avec cette gourde en bois durable. Fabriquée à partir de bois issu de forêts gérées durablement, elle est doublée d’un contenant inox pour garantir une parfaite étanchéité et une conservation optimale des boissons chaudes ou froides. Sa conception sans plastique, réutilisable à l’infini, en fait une alternative idéale aux bouteilles jetables. Solide, stylée et responsable, cette gourde s’adresse à celles et ceux qui veulent consommer autrement, avec respect pour la nature à chaque gorgée.');
        $product3->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($product3);

        $product4 = new Product();
        $product4->setName('Disques Démaquillants x3');
        $product4->setShortDescription('Solution efficace pour vous démaquiller en douceur ');
        $product4->setPrice(19.90);
        $product4->setImageFilename('disque.png');
        $product4->setLongDescription('Fini les cotons jetables ! Optez pour une routine beauté plus responsable avec ces disques démaquillants lavables et réutilisables. Fabriqués en coton bio ou en bambou certifié, ils sont ultra-doux pour la peau, même la plus sensible, tout en respectant l’environnement. Lavables en machine, ils remplacent des centaines de cotons à usage unique et s\’inscrivent parfaitement dans une démarche zéro déchet. Un petit geste au quotidien, pour un grand impact sur la planète.');
        $product4->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($product4);

        $product5 = new Product();
        $product5->setName('Bougie Lavande & Patchouli');
        $product5->setShortDescription('Cire naturelle');
        $product5->setPrice(32.00);
        $product5->setImageFilename('bougie.png');
        $product5->setLongDescription('Laissez-vous envelopper par l’harmonie apaisante de la lavande et la profondeur boisée du patchouli avec cette bougie naturelle, coulée à la main. Composée de cire végétale 100 % naturelle (soja ou colza), d\'une mèche en coton non traité et d’huiles essentielles biologiques, elle ne dégage aucune substance toxique. Son contenant en verre recyclable ou réutilisable renforce sa dimension écoresponsable. Idéale pour créer une ambiance relaxante, cette bougie s’inscrit dans une consommation douce, durable et respectueuse de l’environnement.');
        $product5->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($product5);

        $product6 = new Product();
        $product6->setName('Brosse à dent');
        $product6->setShortDescription('Bois de hêtre rouge issu de forêts gérées durablement');
        $product6->setPrice(59.99);
        $product6->setImageFilename('brosse.png');
        $product6->setLongDescription('Remplacez le plastique de votre salle de bain avec cette brosse à dents en bois naturelle, conçue pour limiter votre impact environnemental sans compromettre votre hygiène bucco-dentaire. Son manche est fabriqué en bois de bambou 100 % biodégradable, issu de forêts gérées durablement, et ses poils doux sans BPA assurent un brossage efficace tout en respectant vos gencives. Une alternative simple et responsable pour réduire vos déchets au quotidien.');
        $product6->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($product6);

        $product7 = new Product();
        $product7->setName('Kit couvert en bois');
        $product7->setShortDescription('Revêtement Bio en olivier & sac de transport');
        $product7->setPrice(12.30);
        $product7->setImageFilename('couvert.png');
        $product7->setLongDescription('Emportez vos couverts partout avec ce kit en bois d’olivier, comprenant une fourchette, un couteau et une cuillère, le tout présenté dans un élégant sac de transport. Idéal pour les pique-niques, les repas en extérieur ou au bureau, ce kit est une alternative durable aux couverts jetables. Chaque pièce est soigneusement fabriquée à partir de bois d’olivier, connu pour sa durabilité et sa résistance à l’eau. Un choix écoresponsable pour réduire vos déchets au quotidien.');
        $product7->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($product7);

        $product8 = new Product();
        $product8->setName('Nécessaire, déodorant Bio');
        $product8->setShortDescription('50ml déodorant à l’eucalyptus');
        $product8->setPrice(8.50);
        $product8->setImageFilename('deodorant.png');
        $product8->setLongDescription('Ce déodorant bio à l’eucalyptus offre une protection efficace tout en respectant votre peau. Sa formule naturelle, sans sels d’aluminium ni parabènes, garantit une sensation de fraîcheur durable. Enrichi en huiles essentielles, il laisse un parfum agréable et subtil. Son format de 50ml est idéal pour une utilisation quotidienne et se glisse facilement dans votre trousse de toilette. Optez pour une hygiène responsable avec ce déodorant respectueux de l’environnement.');
        $product8->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($product8);

        $product9 = new Product();
        $product9->setName('Savon Bio');
        $product9->setShortDescription('Thé, Orange & Girofle');
        $product9->setPrice(18.90);
        $product9->setImageFilename('savon.png');
        $product9->setLongDescription('Savon bio artisanal, fabriqué à partir d\'ingrédients naturels et d\'huiles essentielles. Ce savon doux et parfumé nettoie la peau en profondeur tout en respectant son équilibre naturel. Idéal pour tous les types de peau, il laisse une sensation de fraîcheur et de bien-être. Un choix écoresponsable pour votre routine de soins.');
        $product9->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($product9);

        $manager->flush();
    }
}
