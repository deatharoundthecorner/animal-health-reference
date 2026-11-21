-- Animal Health & Anatomy Reference — sample data
--
-- IMPORTANT: this is STARTER data that demonstrates the structure of the database.
-- It was drafted from general veterinary knowledge and linked to reference pages,
-- but it has NOT been reviewed by veterinarians or biologists. Every record is
-- therefore marked review_status = 'unreviewed' and is shown with a warning badge.
-- Do not use it for clinical decisions. Verify against the cited sources and promote
-- records to 'reviewed' only after a qualified person has checked them.
--
-- Identifiers were checked against the public registries:
--   * NCBI Taxonomy IDs  - https://www.ncbi.nlm.nih.gov/taxonomy
--   * Uberon IDs         - https://obofoundry.org/ontology/uberon.html
--   * Disease Ontology   - https://disease-ontology.org/   (only where a matching term exists)

USE biodiversity_system;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Categories and body systems
-- ---------------------------------------------------------------------------
INSERT INTO categories (category_name) VALUES
('Mammals'), ('Birds'), ('Reptiles'), ('Fish'), ('Amphibians'), ('Insects');

INSERT INTO body_systems (system_name, sort_order) VALUES
('Cardiovascular', 1),
('Respiratory', 2),
('Digestive', 3),
('Nervous', 4),
('Urinary', 5),
('Reproductive', 6),
('Musculoskeletal', 7),
('Integumentary', 8),
('Sensory organs', 9),
('Lymphatic and immune', 10);

-- ---------------------------------------------------------------------------
-- Sources
-- ---------------------------------------------------------------------------
INSERT INTO sources (title, publisher, url, license, source_type) VALUES
('Uberon multi-species anatomy ontology', 'OBO Foundry / Uberon consortium', 'https://obofoundry.org/ontology/uberon.html', 'CC BY (see source)', 'ontology'),
('Human Disease Ontology (DO)', 'Disease Ontology consortium', 'https://disease-ontology.org/', 'CC0 1.0', 'ontology'),
('NCBI Taxonomy', 'National Center for Biotechnology Information', 'https://www.ncbi.nlm.nih.gov/taxonomy', 'Free to use (see NCBI policies)', 'database'),
('IUCN Red List of Threatened Species', 'International Union for Conservation of Nature', 'https://www.iucnredlist.org/', 'Terms of use apply (see IUCN)', 'database'),
('WOAH - Rabies', 'World Organisation for Animal Health', 'https://www.woah.org/en/disease/rabies/', 'Copyright WOAH (link only)', 'database'),
('WOAH - Foot and mouth disease', 'World Organisation for Animal Health', 'https://www.woah.org/en/disease/foot-and-mouth-disease/', 'Copyright WOAH (link only)', 'database'),
('WOAH - African swine fever', 'World Organisation for Animal Health', 'https://www.woah.org/en/disease/african-swine-fever/', 'Copyright WOAH (link only)', 'database'),
('WOAH - Avian influenza', 'World Organisation for Animal Health', 'https://www.woah.org/en/disease/avian-influenza/', 'Copyright WOAH (link only)', 'database'),
('WOAH - Anthrax', 'World Organisation for Animal Health', 'https://www.woah.org/en/disease/anthrax/', 'Copyright WOAH (link only)', 'database'),
('WOAH - Brucellosis', 'World Organisation for Animal Health', 'https://www.woah.org/en/disease/brucellosis/', 'Copyright WOAH (link only)', 'database'),
('WOAH - Bovine tuberculosis', 'World Organisation for Animal Health', 'https://www.woah.org/en/disease/bovine-tuberculosis/', 'Copyright WOAH (link only)', 'database'),
('WOAH - Myxomatosis', 'World Organisation for Animal Health', 'https://www.woah.org/en/disease/myxomatosis/', 'Copyright WOAH (link only)', 'database'),
('WOAH - Chytridiomycosis (Batrachochytrium dendrobatidis)', 'World Organisation for Animal Health', 'https://www.woah.org/en/disease/chytridiomycosis-batrachochytrium-dendrobatidis/', 'Copyright WOAH (link only)', 'database'),
('Merck Veterinary Manual - Canine parvovirus', 'Merck & Co., Inc.', 'https://www.merckvetmanual.com/digestive-system/diseases-of-the-stomach-and-intestines-in-small-animals/canine-parvovirus', 'Copyright Merck & Co., Inc. (link only)', 'manual'),
('Merck Veterinary Manual - Feline panleukopenia', 'Merck & Co., Inc.', 'https://www.merckvetmanual.com/generalized-conditions/feline-panleukopenia/overview-of-feline-panleukopenia', 'Copyright Merck & Co., Inc. (link only)', 'manual'),
('Merck Veterinary Manual - Canine distemper', 'Merck & Co., Inc.', 'https://www.merckvetmanual.com/generalized-conditions/canine-distemper/canine-distemper-overview', 'Copyright Merck & Co., Inc. (link only)', 'manual'),
('Merck Veterinary Manual - Feline respiratory disease complex', 'Merck & Co., Inc.', 'https://www.merckvetmanual.com/respiratory-system/respiratory-diseases-of-small-animals/feline-respiratory-disease-complex', 'Copyright Merck & Co., Inc. (link only)', 'manual'),
('Merck Veterinary Manual - Colic in horses', 'Merck & Co., Inc.', 'https://www.merckvetmanual.com/digestive-system/colic-in-horses/overview-of-colic-in-horses', 'Copyright Merck & Co., Inc. (link only)', 'manual'),
('Merck Veterinary Manual - Mastitis in cattle', 'Merck & Co., Inc.', 'https://www.merckvetmanual.com/reproductive-system/mastitis-in-large-animals/mastitis-in-cattle', 'Copyright Merck & Co., Inc. (link only)', 'manual'),
('Merck Veterinary Manual - Leptospirosis in animals', 'Merck & Co., Inc.', 'https://www.merckvetmanual.com/generalized-conditions/leptospirosis/leptospirosis-in-animals-overview', 'Copyright Merck & Co., Inc. (link only)', 'manual'),
('Merck Veterinary Manual - Heartworm disease in dogs, cats, and ferrets', 'Merck & Co., Inc.', 'https://www.merckvetmanual.com/circulatory-system/heartworm-disease/heartworm-disease-in-dogs-cats-and-ferrets', 'Copyright Merck & Co., Inc. (link only)', 'manual'),
('WHO - Rabies fact sheet', 'World Health Organization', 'https://www.who.int/news-room/fact-sheets/detail/rabies', 'Copyright WHO (link only)', 'database'),
('WHO - Brucellosis fact sheet', 'World Health Organization', 'https://www.who.int/news-room/fact-sheets/detail/brucellosis', 'Copyright WHO (link only)', 'database'),
('WSAVA Vaccination Guidelines', 'World Small Animal Veterinary Association', 'https://wsava.org/global-guidelines/vaccination-guidelines/', 'Copyright WSAVA (link only)', 'guideline'),
('American Heartworm Society', 'American Heartworm Society', 'https://www.heartwormsociety.org/', 'Copyright AHS (link only)', 'guideline'),
('USGS - Sea turtle diseases', 'U.S. Geological Survey', 'https://www.usgs.gov/diseases-of-aquatic-organisms/sea-turtle-diseases', 'Public domain unless otherwise noted', 'database'),
('Long SY, Latimer EM, Hayward GS (2016). Review of Elephant Endotheliotropic Herpesviruses and Acute Hemorrhagic Disease. ILAR J', 'ILAR Journal (via PubMed Central)', 'https://pmc.ncbi.nlm.nih.gov/articles/PMC4765743', 'See article', 'article');

-- ---------------------------------------------------------------------------
-- Body parts (Uberon IDs verified against the Ontology Lookup Service)
-- ---------------------------------------------------------------------------
INSERT INTO body_parts (system_id, part_name, uberon_id, description, review_status) VALUES
((SELECT id FROM body_systems WHERE system_name = 'Cardiovascular'), 'Heart', 'UBERON:0000948', 'Muscular organ that pumps blood around the body. Mammals and birds have a four-chambered heart.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Cardiovascular'), 'Blood vessel', 'UBERON:0001981', 'Arteries, veins and capillaries that carry blood between the heart and the tissues.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Cardiovascular'), 'Blood', 'UBERON:0000178', 'Fluid tissue made of red cells, white cells, platelets and plasma that transports oxygen, nutrients, hormones and immune cells.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Respiratory'), 'Nose', 'UBERON:0000004', 'Entrance to the upper airway. Warms, filters and humidifies inhaled air and carries the sense of smell.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Respiratory'), 'Trachea', 'UBERON:0003126', 'Cartilage-supported airway that connects the larynx to the bronchi.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Respiratory'), 'Lung', 'UBERON:0002048', 'Organ of gas exchange. Mammalian lungs expand and contract with breathing; avian lungs are rigid and ventilated through air sacs.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Digestive'), 'Mouth', 'UBERON:0000165', 'Oral cavity containing the teeth (or beak), tongue and the openings of the salivary glands. First part of the digestive tract.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Digestive'), 'Esophagus', 'UBERON:0001043', 'Muscular tube that carries food from the pharynx to the stomach.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Digestive'), 'Stomach', 'UBERON:0000945', 'Stores food and begins chemical digestion. Structure varies widely between species: simple (single-chambered) in dogs, cats, pigs and horses; four-compartment in ruminants; two-part in birds.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Digestive'), 'Rumen', 'UBERON:0007365', 'Largest compartment of the ruminant forestomach. A fermentation chamber in which microbes break down plant fiber.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Digestive'), 'Small intestine', 'UBERON:0002108', 'Main site of digestion and nutrient absorption (duodenum, jejunum and ileum).', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Digestive'), 'Large intestine', 'UBERON:0000059', 'Absorbs water and electrolytes. In hindgut fermenters such as horses and rabbits it also ferments plant fiber (cecum and colon).', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Digestive'), 'Liver', 'UBERON:0002107', 'Large organ that processes nutrients, produces bile, stores glycogen and detoxifies the blood.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Digestive'), 'Pancreas', 'UBERON:0001264', 'Produces digestive enzymes and the hormones insulin and glucagon.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Nervous'), 'Brain', 'UBERON:0000955', 'Center of the nervous system that controls behavior, sensation and movement.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Nervous'), 'Spinal cord', 'UBERON:0002240', 'Nerve tract running through the vertebral canal that relays signals between the brain and the body.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Urinary'), 'Kidney', 'UBERON:0002113', 'Filters the blood to make urine and regulates fluid, electrolyte and acid-base balance.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Urinary'), 'Urinary bladder', 'UBERON:0001255', 'Stores urine before it is passed. Most birds do not have a urinary bladder.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Reproductive'), 'Uterus', 'UBERON:0000995', 'Female reproductive organ in which the fetus develops. Birds have an oviduct instead.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Reproductive'), 'Ovary', 'UBERON:0000992', 'Female gonad that produces eggs and sex hormones.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Reproductive'), 'Testis', 'UBERON:0000473', 'Male gonad that produces sperm and testosterone.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Reproductive'), 'Mammary gland', 'UBERON:0001911', 'Milk-producing gland (the udder in ruminants and horses).', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Musculoskeletal'), 'Bone', 'UBERON:0001474', 'Rigid skeletal element that supports the body, protects organs and stores minerals.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Musculoskeletal'), 'Skeletal muscle', 'UBERON:0014892', 'Voluntary muscle attached to the skeleton that produces movement.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Musculoskeletal'), 'Joint', 'UBERON:0002217', 'Junction between bones that allows movement. Synovial joints are lubricated by joint fluid.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Musculoskeletal'), 'Foot', 'UBERON:0002387', 'Distal part of the limb: paws in dogs and cats, hooves in horses and ruminants.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Integumentary'), 'Skin', 'UBERON:0002097', 'Outer covering of the body. Barrier against infection, injury and water loss; bears hair, feathers or scales depending on the species.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Sensory organs'), 'Eye', 'UBERON:0000970', 'Organ of vision.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Sensory organs'), 'Ear', 'UBERON:0001690', 'Organ of hearing and balance.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Lymphatic and immune'), 'Spleen', 'UBERON:0002106', 'Filters the blood, stores red cells and platelets and takes part in immune responses.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Lymphatic and immune'), 'Lymph node', 'UBERON:0000029', 'Filters lymph and houses immune cells that respond to infection. Birds generally lack true lymph nodes.', 'unreviewed'),
((SELECT id FROM body_systems WHERE system_name = 'Lymphatic and immune'), 'Bone marrow', 'UBERON:0002371', 'Soft tissue inside bones where blood cells are produced.', 'unreviewed');

INSERT INTO body_part_sources (body_part_id, source_id)
SELECT bp.id, s.id
FROM body_parts bp
CROSS JOIN sources s
WHERE s.title = 'Uberon multi-species anatomy ontology'
  AND bp.uberon_id IS NOT NULL;

-- ---------------------------------------------------------------------------
-- Animals (NCBI Taxonomy IDs and lineage verified)
-- ---------------------------------------------------------------------------
INSERT INTO animals
(common_name, scientific_name, taxon_rank, ncbi_taxon_id, category_id, animal_type, class_name, order_name, family_name, habitat, diet, country, lifespan, conservation_status, description, review_status)
VALUES
('Dog', 'Canis lupus familiaris', 'subspecies', 9615, (SELECT id FROM categories WHERE category_name = 'Mammals'), 'companion', 'Mammalia', 'Carnivora', 'Canidae',
 'Domestic (households, farms and working roles)', 'Omnivore (facultative carnivore)', 'Worldwide', '10-13 years (varies by breed)', 'Domesticated (not assessed)',
 'Domesticated carnivore kept as a companion, working and guard animal. Dogs are a reservoir of several zoonoses, including rabies, and are prone to infectious diseases that are largely preventable by vaccination.', 'unreviewed'),
('Cat', 'Felis catus', 'species', 9685, (SELECT id FROM categories WHERE category_name = 'Mammals'), 'companion', 'Mammalia', 'Carnivora', 'Felidae',
 'Domestic (households and farms)', 'Obligate carnivore', 'Worldwide', '12-18 years', 'Domesticated (not assessed)',
 'Obligate carnivore kept as a companion animal and for pest control. Cats are susceptible to several vaccine-preventable viral diseases and are the definitive host of Toxoplasma gondii.', 'unreviewed'),
('Cattle', 'Bos taurus', 'species', 9913, (SELECT id FROM categories WHERE category_name = 'Mammals'), 'livestock', 'Mammalia', 'Artiodactyla', 'Bovidae',
 'Farms and pasture', 'Herbivore (ruminant)', 'Worldwide', '15-20 years (commercial animals are usually culled earlier)', 'Domesticated (not assessed)',
 'Large ruminant farmed for meat, milk and draught power. Cattle are central to national control programs for notifiable and zoonotic diseases such as tuberculosis and brucellosis.', 'unreviewed'),
('Horse', 'Equus caballus', 'species', 9796, (SELECT id FROM categories WHERE category_name = 'Mammals'), 'livestock', 'Mammalia', 'Perissodactyla', 'Equidae',
 'Stables, pasture and working environments', 'Herbivore (hindgut fermenter)', 'Worldwide', '25-30 years', 'Domesticated (not assessed)',
 'Large hindgut-fermenting herbivore used for riding, sport and work. Horses cannot vomit, and their digestive anatomy makes them prone to colic.', 'unreviewed'),
('Domestic Pig', 'Sus scrofa domesticus', 'subspecies', 9825, (SELECT id FROM categories WHERE category_name = 'Mammals'), 'livestock', 'Mammalia', 'Artiodactyla', 'Suidae',
 'Farms and smallholdings', 'Omnivore', 'Worldwide', '15-20 years (commercial animals are usually slaughtered much younger)', 'Domesticated (not assessed)',
 'Monogastric omnivore farmed for meat. Pigs are susceptible to several highly contagious notifiable diseases, including African swine fever and foot-and-mouth disease.', 'unreviewed'),
('Sheep', 'Ovis aries', 'species', 9940, (SELECT id FROM categories WHERE category_name = 'Mammals'), 'livestock', 'Mammalia', 'Artiodactyla', 'Bovidae',
 'Farms, pasture and rangeland', 'Herbivore (ruminant grazer)', 'Worldwide', '10-12 years', 'Domesticated (not assessed)',
 'Small ruminant farmed for wool, meat and milk. Sheep share many infectious diseases with goats and cattle.', 'unreviewed'),
('Goat', 'Capra hircus', 'species', 9925, (SELECT id FROM categories WHERE category_name = 'Mammals'), 'livestock', 'Mammalia', 'Artiodactyla', 'Bovidae',
 'Farms, pasture and rangeland', 'Herbivore (ruminant browser)', 'Worldwide', '10-15 years', 'Domesticated (not assessed)',
 'Small ruminant farmed for milk, meat and fiber. Goats are browsers and are affected by many of the same infectious diseases as sheep and cattle.', 'unreviewed'),
('Chicken', 'Gallus gallus', 'species', 9031, (SELECT id FROM categories WHERE category_name = 'Birds'), 'livestock', 'Aves', 'Galliformes', 'Phasianidae',
 'Farms, backyards and commercial poultry houses', 'Omnivore', 'Worldwide', '5-8 years', 'Domesticated (not assessed)',
 'Domesticated bird farmed for eggs and meat. Birds differ from mammals in their respiratory and digestive anatomy (air sacs, crop and gizzard), and chickens are central to avian influenza surveillance.', 'unreviewed'),
('Rabbit', 'Oryctolagus cuniculus', 'species', 9986, (SELECT id FROM categories WHERE category_name = 'Mammals'), 'companion', 'Mammalia', 'Lagomorpha', 'Leporidae',
 'Hutches, households and farms', 'Herbivore (hindgut fermenter)', 'Worldwide', '8-12 years', 'Domesticated (not assessed)',
 'Small herbivore kept as a pet and farmed for meat. Rabbit teeth grow continuously and the digestive system depends on a high-fiber diet and on re-ingestion of cecotropes.', 'unreviewed'),
('Asian Elephant', 'Elephas maximus', 'species', 9783, (SELECT id FROM categories WHERE category_name = 'Mammals'), 'wildlife', 'Mammalia', 'Proboscidea', 'Elephantidae',
 'Tropical forests and grasslands', 'Herbivore', 'Sri Lanka, India, Thailand', '48-60 years', 'Endangered',
 'The Asian elephant is smaller than its African relative and is threatened by habitat loss and human-elephant conflict.', 'unreviewed'),
('Blue Whale', 'Balaenoptera musculus', 'species', 9771, (SELECT id FROM categories WHERE category_name = 'Mammals'), 'wildlife', 'Mammalia', 'Artiodactyla', 'Balaenopteridae',
 'Open ocean', 'Carnivore (krill)', 'Global oceans', '70-90 years', 'Endangered',
 'The blue whale is the largest known animal in history and remains vulnerable to climate and ecosystem change.', 'unreviewed'),
('Green Sea Turtle', 'Chelonia mydas', 'species', 8469, (SELECT id FROM categories WHERE category_name = 'Reptiles'), 'wildlife', 'Reptilia', 'Testudines', 'Cheloniidae',
 'Coastal waters and seagrass beds', 'Herbivore (adult)', 'Tropical and subtropical seas', '60-80 years', 'Least Concern',
 'Green sea turtles migrate long distances and are still threatened by bycatch, plastic pollution and habitat degradation. The global population was reassessed from Endangered to Least Concern in 2025, but recovery is uneven between regions.', 'unreviewed'),
('Red Panda', 'Ailurus fulgens', 'species', 9649, (SELECT id FROM categories WHERE category_name = 'Mammals'), 'wildlife', 'Mammalia', 'Carnivora', 'Ailuridae',
 'Temperate mountain forests', 'Omnivore (mostly bamboo)', 'Nepal, India, Bhutan, China', '8-15 years', 'Endangered',
 'Red pandas are arboreal mammals with a specialized diet and are at risk due to deforestation and fragmentation.', 'unreviewed'),
('Bald Eagle', 'Haliaeetus leucocephalus', 'species', 52644, (SELECT id FROM categories WHERE category_name = 'Birds'), 'wildlife', 'Aves', 'Accipitriformes', 'Accipitridae',
 'Forests near water bodies', 'Carnivore', 'North America', '20-30 years', 'Least Concern',
 'A powerful bird of prey and a conservation success story after strict legal protection and habitat recovery.', 'unreviewed'),
('Poison Dart Frog', 'Dendrobatidae', 'family', 43469, (SELECT id FROM categories WHERE category_name = 'Amphibians'), 'wildlife', 'Amphibia', 'Anura', 'Dendrobatidae',
 'Tropical rainforests', 'Insectivore', 'Central and South America', '10-15 years (varies by species)', 'Varies by species',
 'Poison dart frogs are a family of small, often brightly colored frogs. Their warning colors and skin toxins are a defense mechanism. Conservation status differs between species.', 'unreviewed'),
('Monarch Butterfly', 'Danaus plexippus', 'species', 13037, (SELECT id FROM categories WHERE category_name = 'Insects'), 'wildlife', 'Insecta', 'Lepidoptera', 'Nymphalidae',
 'Meadows and open fields', 'Nectar and milkweed', 'North America', '6-8 months (migratory generation)', 'Vulnerable (migratory subspecies)',
 'Monarch butterflies undertake one of the most remarkable migrations. The migratory subspecies is affected by pesticide use and habitat loss.', 'unreviewed'),
('Clownfish', 'Amphiprion', 'genus', 80969, (SELECT id FROM categories WHERE category_name = 'Fish'), 'wildlife', 'Actinopterygii', NULL, 'Pomacentridae',
 'Coral reefs', 'Omnivore', 'Indo-Pacific', '6-10 years', 'Varies by species',
 'Clownfish live symbiotically with sea anemones and are a key part of reef biodiversity.', 'unreviewed');

INSERT INTO animal_sources (animal_id, source_id)
SELECT a.id, s.id FROM animals a CROSS JOIN sources s
WHERE s.title = 'NCBI Taxonomy' AND a.ncbi_taxon_id IS NOT NULL;

INSERT INTO animal_sources (animal_id, source_id)
SELECT a.id, s.id FROM animals a CROSS JOIN sources s
WHERE s.title = 'IUCN Red List of Threatened Species' AND a.animal_type = 'wildlife';

-- ---------------------------------------------------------------------------
-- Which body parts each animal has
-- ---------------------------------------------------------------------------
-- Mammals (the whale is added separately because it has no feet or external nose)
INSERT INTO animal_body_parts (animal_id, body_part_id)
SELECT a.id, bp.id
FROM animals a
CROSS JOIN body_parts bp
WHERE a.common_name IN ('Dog', 'Cat', 'Cattle', 'Horse', 'Domestic Pig', 'Sheep', 'Goat', 'Rabbit', 'Asian Elephant', 'Red Panda')
  AND bp.part_name IN ('Heart', 'Blood vessel', 'Blood', 'Nose', 'Trachea', 'Lung', 'Mouth', 'Esophagus', 'Stomach',
                       'Small intestine', 'Large intestine', 'Liver', 'Pancreas', 'Brain', 'Spinal cord', 'Kidney',
                       'Urinary bladder', 'Uterus', 'Ovary', 'Testis', 'Mammary gland', 'Bone', 'Skeletal muscle',
                       'Joint', 'Foot', 'Skin', 'Eye', 'Ear', 'Spleen', 'Lymph node', 'Bone marrow');

INSERT INTO animal_body_parts (animal_id, body_part_id)
SELECT a.id, bp.id
FROM animals a
CROSS JOIN body_parts bp
WHERE a.common_name = 'Blue Whale'
  AND bp.part_name IN ('Heart', 'Blood vessel', 'Blood', 'Trachea', 'Lung', 'Mouth', 'Esophagus', 'Stomach',
                       'Small intestine', 'Large intestine', 'Liver', 'Pancreas', 'Brain', 'Spinal cord', 'Kidney',
                       'Uterus', 'Ovary', 'Testis', 'Mammary gland', 'Bone', 'Skeletal muscle', 'Joint', 'Skin',
                       'Eye', 'Ear', 'Spleen', 'Lymph node', 'Bone marrow');

-- Ruminants also have a rumen
INSERT INTO animal_body_parts (animal_id, body_part_id)
SELECT a.id, bp.id
FROM animals a
CROSS JOIN body_parts bp
WHERE a.common_name IN ('Cattle', 'Sheep', 'Goat')
  AND bp.part_name = 'Rumen';

-- Birds (no nose, urinary bladder, uterus, mammary gland or true lymph nodes)
INSERT INTO animal_body_parts (animal_id, body_part_id)
SELECT a.id, bp.id
FROM animals a
CROSS JOIN body_parts bp
WHERE a.common_name IN ('Chicken', 'Bald Eagle')
  AND bp.part_name IN ('Heart', 'Blood vessel', 'Blood', 'Trachea', 'Lung', 'Mouth', 'Esophagus', 'Stomach',
                       'Small intestine', 'Large intestine', 'Liver', 'Pancreas', 'Brain', 'Spinal cord', 'Kidney',
                       'Ovary', 'Testis', 'Bone', 'Skeletal muscle', 'Joint', 'Foot', 'Skin', 'Eye', 'Ear',
                       'Spleen', 'Bone marrow');

-- Green sea turtle (partial coverage)
INSERT INTO animal_body_parts (animal_id, body_part_id)
SELECT a.id, bp.id
FROM animals a
CROSS JOIN body_parts bp
WHERE a.common_name = 'Green Sea Turtle'
  AND bp.part_name IN ('Heart', 'Lung', 'Mouth', 'Stomach', 'Small intestine', 'Large intestine', 'Liver', 'Brain',
                       'Kidney', 'Bone', 'Skin', 'Eye');

-- Poison dart frogs (partial coverage)
INSERT INTO animal_body_parts (animal_id, body_part_id)
SELECT a.id, bp.id
FROM animals a
CROSS JOIN body_parts bp
WHERE a.common_name = 'Poison Dart Frog'
  AND bp.part_name IN ('Heart', 'Lung', 'Stomach', 'Small intestine', 'Liver', 'Kidney', 'Brain', 'Skin', 'Eye',
                       'Skeletal muscle', 'Bone');

-- Species-specific anatomical notes
UPDATE animal_body_parts abp, animals a, body_parts bp,
(
    SELECT 'Dog' AS animal, 'Mouth' AS part, '42 permanent teeth.' AS notes
    UNION ALL SELECT 'Cat', 'Mouth', '30 permanent teeth; carnivore dentition.'
    UNION ALL SELECT 'Cattle', 'Mouth', '32 permanent teeth; no upper incisors (a dental pad is present instead).'
    UNION ALL SELECT 'Sheep', 'Mouth', '32 permanent teeth; no upper incisors (a dental pad is present instead).'
    UNION ALL SELECT 'Goat', 'Mouth', '32 permanent teeth; no upper incisors (a dental pad is present instead).'
    UNION ALL SELECT 'Horse', 'Mouth', '36-44 permanent teeth (males usually have 40). Cheek teeth erupt continuously and wear down through life.'
    UNION ALL SELECT 'Domestic Pig', 'Mouth', '44 permanent teeth.'
    UNION ALL SELECT 'Rabbit', 'Mouth', '28 permanent teeth; incisors and cheek teeth grow continuously.'
    UNION ALL SELECT 'Chicken', 'Mouth', 'No teeth. The beak picks up food and the muscular gizzard grinds it.'
    UNION ALL SELECT 'Bald Eagle', 'Mouth', 'No teeth. Hooked beak used to tear flesh.'
    UNION ALL SELECT 'Green Sea Turtle', 'Mouth', 'Toothless beak. Adults feed mainly on seagrass and algae.'
    UNION ALL SELECT 'Asian Elephant', 'Mouth', 'Large molars are replaced horizontally through life. Tusks are elongated incisors and are small or absent in many Asian elephants, especially females.'
    UNION ALL SELECT 'Asian Elephant', 'Nose', 'The trunk is a fusion of the nose and upper lip.'
    UNION ALL SELECT 'Asian Elephant', 'Testis', 'Testes are held inside the abdomen.'
    UNION ALL SELECT 'Dog', 'Stomach', 'Simple (single-chambered) stomach; dogs vomit readily.'
    UNION ALL SELECT 'Cat', 'Stomach', 'Simple (single-chambered) stomach.'
    UNION ALL SELECT 'Domestic Pig', 'Stomach', 'Simple (single-chambered) stomach.'
    UNION ALL SELECT 'Horse', 'Stomach', 'Simple stomach that is small relative to body size. Horses cannot vomit, so gastric distension is dangerous.'
    UNION ALL SELECT 'Cattle', 'Stomach', 'Ruminant stomach with four compartments (rumen, reticulum, omasum and abomasum). The abomasum is the glandular, true stomach.'
    UNION ALL SELECT 'Sheep', 'Stomach', 'Ruminant stomach with four compartments (rumen, reticulum, omasum and abomasum).'
    UNION ALL SELECT 'Goat', 'Stomach', 'Ruminant stomach with four compartments (rumen, reticulum, omasum and abomasum).'
    UNION ALL SELECT 'Chicken', 'Stomach', 'Two-part stomach: the glandular proventriculus and the muscular gizzard. Food is stored first in the crop.'
    UNION ALL SELECT 'Bald Eagle', 'Stomach', 'Two-part stomach: the glandular proventriculus and the muscular gizzard.'
    UNION ALL SELECT 'Horse', 'Large intestine', 'Hindgut fermenter: the large cecum and colon ferment fiber. Impaction and displacement of the large colon are common causes of colic.'
    UNION ALL SELECT 'Rabbit', 'Large intestine', 'Large cecum. Rabbits produce soft cecotropes and re-ingest them (cecotrophy).'
    UNION ALL SELECT 'Chicken', 'Lung', 'Small, rigid lungs connected to air sacs. Birds have no muscular diaphragm.'
    UNION ALL SELECT 'Bald Eagle', 'Lung', 'Small, rigid lungs connected to air sacs. Birds have no muscular diaphragm.'
    UNION ALL SELECT 'Blue Whale', 'Lung', 'Lungs adapted for deep dives. The whale breathes through paired blowholes.'
    UNION ALL SELECT 'Poison Dart Frog', 'Lung', 'Simple sac-like lungs; frogs also exchange gases through the skin.'
    UNION ALL SELECT 'Poison Dart Frog', 'Heart', 'Three-chambered heart (two atria and one ventricle).'
    UNION ALL SELECT 'Poison Dart Frog', 'Skin', 'Thin, moist, permeable skin used for water uptake and gas exchange. Many dendrobatids carry toxic skin secretions.'
    UNION ALL SELECT 'Chicken', 'Ovary', 'Only the left ovary and oviduct are functional in hens.'
    UNION ALL SELECT 'Chicken', 'Testis', 'Testes are internal, inside the body cavity.'
    UNION ALL SELECT 'Cattle', 'Mammary gland', 'Udder with four quarters, each with one teat.'
    UNION ALL SELECT 'Sheep', 'Mammary gland', 'Udder with two halves, each with one teat.'
    UNION ALL SELECT 'Goat', 'Mammary gland', 'Udder with two halves, each with one teat.'
    UNION ALL SELECT 'Cattle', 'Foot', 'Cloven (two-toed) hoof.'
    UNION ALL SELECT 'Sheep', 'Foot', 'Cloven (two-toed) hoof.'
    UNION ALL SELECT 'Goat', 'Foot', 'Cloven (two-toed) hoof.'
    UNION ALL SELECT 'Domestic Pig', 'Foot', 'Cloven (two-toed) hoof with two smaller dewclaws.'
    UNION ALL SELECT 'Horse', 'Foot', 'Single hoof (one functional toe).'
    UNION ALL SELECT 'Dog', 'Foot', 'Walks on the toes (digitigrade) with paw pads and non-retractable claws.'
    UNION ALL SELECT 'Cat', 'Foot', 'Walks on the toes (digitigrade) with paw pads and retractable claws.'
    UNION ALL SELECT 'Red Panda', 'Foot', 'Semi-retractable claws used for climbing.'
    UNION ALL SELECT 'Green Sea Turtle', 'Bone', 'The shell (carapace and plastron) is formed from bone fused with the ribs and spine.'
    UNION ALL SELECT 'Green Sea Turtle', 'Eye', 'Salt glands near the eye excrete excess salt.'
) x
SET abp.notes = x.notes
WHERE a.common_name = x.animal
  AND bp.part_name = x.part
  AND abp.animal_id = a.id
  AND abp.body_part_id = bp.id;

-- ---------------------------------------------------------------------------
-- Symptoms
-- ---------------------------------------------------------------------------
INSERT INTO symptoms (symptom_name) VALUES
('abdominal distension'), ('abdominal pain'), ('abnormal milk'), ('abnormal skin shedding'), ('abortion'),
('aggression'), ('behavior change'), ('bloody diarrhea'), ('conjunctivitis'), ('cough'),
('cyanosis'), ('decreased egg production'), ('dehydration'), ('diarrhea'), ('difficulty breathing'),
('excessive salivation'), ('exercise intolerance'), ('eye discharge'), ('fever'), ('hemorrhages'),
('impaired vision'), ('increased thirst'), ('infertility'), ('jaundice'), ('lameness'),
('lethargy'), ('loss of appetite'), ('nasal discharge'), ('oral blisters or ulcers'), ('paralysis'),
('reduced manure output'), ('reduced milk production'), ('restlessness and rolling'), ('seizures'),
('skin growths (tumors)'), ('skin lesions'), ('skin redness'), ('sneezing'), ('sudden death'),
('swelling of head or face'), ('swollen udder'), ('sweating'), ('testicular swelling'), ('vomiting'),
('weight loss');

-- ---------------------------------------------------------------------------
-- Diseases
-- ---------------------------------------------------------------------------
INSERT INTO diseases
(disease_name, pathogen_type, pathogen_name, disease_ontology_id, is_zoonotic, woah_listed, summary, transmission, prevention, review_status)
VALUES
('Rabies', 'virus', 'Rabies lyssavirus', 'DOID:11260', 1, 1,
 'Almost invariably fatal viral encephalitis of mammals. The virus infects the central nervous system and is usually spread in the saliva of an infected animal, most often through a bite.',
 'Bite from an infected animal (the virus is shed in saliva); less commonly contamination of broken skin or mucous membranes. Wildlife reservoirs such as bats and foxes, and free-roaming dogs in endemic regions, maintain the virus.',
 'Vaccination of dogs, cats and livestock where required; control of stray dog populations; post-exposure prophylaxis for exposed people; report suspected cases to the veterinary authorities.',
 'unreviewed'),
('Canine parvovirus enteritis', 'virus', 'Canine parvovirus 2 (CPV-2)', NULL, 0, 0,
 'Highly contagious viral gastroenteritis of dogs, most severe in puppies and unvaccinated dogs. The virus destroys rapidly dividing cells of the intestinal lining and bone marrow, causing vomiting, bloody diarrhea and dehydration.',
 'Fecal-oral: contact with infected feces or with surfaces, objects or people contaminated by the virus. The virus is very stable and survives in the environment for months.',
 'Core vaccination series for puppies with boosters (see WSAVA guidelines); isolate affected dogs; disinfect with products that are effective against non-enveloped viruses, such as dilute bleach.',
 'unreviewed'),
('Canine distemper', 'virus', 'Canine morbillivirus (canine distemper virus)', NULL, 0, 0,
 'Highly contagious multisystem viral disease of dogs and other carnivores. It affects the respiratory, gastrointestinal and nervous systems and is often fatal; survivors may have permanent neurological signs.',
 'Mainly aerosol droplets from infected animals. Infected dogs can shed virus for weeks to months.',
 'Core vaccination (generally effective) and isolation of sick animals.',
 'unreviewed'),
('Feline panleukopenia', 'virus', 'Feline parvovirus (FPV)', NULL, 0, 0,
 'Highly contagious parvoviral disease of cats, most severe in kittens. It causes fever, vomiting, diarrhea and a sharp fall in white blood cells; infection in the womb or in very young kittens can damage the cerebellum.',
 'Oral contact with infected cats, their feces or vomit, or contaminated objects. The virus can persist in the environment for up to a year.',
 'Core vaccination (highly effective); isolate sick cats; disinfect with strong oxidizing agents such as diluted bleach. Quaternary ammonium products are not reliably effective.',
 'unreviewed'),
('Feline respiratory disease complex', 'multiple', 'Feline herpesvirus 1 (viral rhinotracheitis), feline calicivirus and others', NULL, 0, 0,
 'Common contagious upper respiratory syndrome of cats, mainly caused by feline herpesvirus 1 and feline calicivirus, sometimes complicated by secondary bacterial infection.',
 'Direct contact with discharges from infected cats, short-distance droplets and contaminated objects. Herpesvirus stays latent after recovery and can reactivate during stress.',
 'Core vaccination (reduces severity but does not always prevent infection); reduce stress and overcrowding; isolate sick cats.',
 'unreviewed'),
('Foot-and-mouth disease', 'virus', 'Foot-and-mouth disease virus (Aphthovirus)', NULL, 0, 1,
 'Highly contagious viral disease of cloven-hoofed animals that causes fever and blisters on the mouth, feet and teats. Death rates are low in adults, but the disease causes severe production losses and trade restrictions. Human infection is rare.',
 'Direct contact, aerosols, and contaminated people, vehicles, feed and animal products. Infected animals shed virus in breath, saliva, milk and other secretions.',
 'Biosecurity and movement control; vaccination where the disease is endemic (vaccines are serotype-specific); stamping-out policies in disease-free countries; immediate reporting to the authorities.',
 'unreviewed'),
('Bovine tuberculosis', 'bacteria', 'Mycobacterium bovis', NULL, 1, 1,
 'Chronic bacterial disease of cattle and other mammals that forms granulomatous lesions (tubercles), most often in the lungs and lymph nodes. Many infected animals show no signs for a long time. It can spread to people through raw milk or close contact.',
 'Inhalation of aerosols from infected animals, and ingestion of contaminated milk or feed. Wildlife reservoirs (for example badgers, deer and wild boar) exist in some regions.',
 'Test-and-cull programs and movement testing; pasteurization of milk; wildlife management where reservoirs exist.',
 'unreviewed'),
('Brucellosis', 'bacteria', 'Brucella spp. (B. abortus, B. melitensis, B. suis, B. canis, B. ovis)', 'DOID:11077', 1, 1,
 'Bacterial disease that causes reproductive failure (abortion and infertility) in livestock and dogs. Different Brucella species favor different hosts. It is one of the most common bacterial zoonoses.',
 'Contact with aborted fetuses, placental fluids and vaginal discharges; ingestion of contaminated feed or milk; venereal spread. People are infected through raw milk or contact with infected animals.',
 'Vaccination in endemic areas, test-and-slaughter policies, hygiene when handling birthing material, and pasteurization of milk.',
 'unreviewed'),
('Mastitis', 'multiple', 'Streptococci, staphylococci, coliforms and other bacteria', NULL, 0, 0,
 'Inflammation of the mammary gland, usually caused by bacteria entering through the teat canal. It is one of the most economically important diseases of dairy animals. Clinical cases produce abnormal milk; subclinical cases show no visible signs but raise the milk somatic cell count.',
 'Contagious bacteria spread mainly during milking; environmental bacteria come from bedding, mud and manure.',
 'Milking hygiene, teat disinfection, well-maintained milking machines, clean dry bedding, dry-cow management and culling of chronically infected animals.',
 'unreviewed'),
('Equine colic', 'non-infectious', NULL, NULL, 0, 0,
 'Abdominal pain in horses from many different causes, ranging from mild gas or impaction to life-threatening twisting or strangulation of the intestine. Colic is one of the most common emergencies in horses and always warrants prompt veterinary assessment.',
 'Not contagious. Recognized risk factors include abrupt feed changes, low water intake, dental problems, parasites, limited turnout and sand ingestion.',
 'Consistent feeding with gradual diet changes, constant access to water, regular dental care, parasite control and regular exercise or turnout.',
 'unreviewed'),
('Highly pathogenic avian influenza', 'virus', 'Influenza A virus (H5 and H7 subtypes)', 'DOID:4492', 1, 1,
 'Highly contagious viral disease of poultry and wild birds with very high mortality in chickens. Some strains can infect mammals, including people.',
 'Direct contact with infected birds and their secretions, and contaminated equipment, feed and water. Wild waterfowl are natural reservoirs.',
 'Biosecurity, preventing contact with wild birds, rapid reporting, culling and movement control during outbreaks. Vaccination only under authority-approved programs.',
 'unreviewed'),
('Myxomatosis', 'virus', 'Myxoma virus', NULL, 0, 1,
 'Severe viral disease of European rabbits with swelling of the face and genitals, skin nodules and high mortality. It is spread mainly by biting insects.',
 'Bites of fleas, mosquitoes and other biting insects; also direct contact.',
 'Vaccination where available, insect control, keeping pet rabbits away from wild rabbits, and prompt isolation of sick animals.',
 'unreviewed'),
('Leptospirosis', 'bacteria', 'Leptospira interrogans (pathogenic serovars)', 'DOID:2297', 1, 0,
 'Bacterial disease that affects many mammals, from subclinical infection to kidney and liver failure. Animals shed the bacteria in urine, and people can be infected through contact with contaminated water, soil or urine.',
 'Direct contact with infected urine, placental fluids or milk; indirect contact with contaminated water or soil, especially in warm, moist conditions.',
 'Serovar-specific vaccination of dogs and livestock, rodent control, avoiding stagnant water, and hygiene when handling infected animals.',
 'unreviewed'),
('Heartworm disease', 'parasite', 'Dirofilaria immitis', 'DOID:1082', 0, 0,
 'Mosquito-borne parasitic disease in which adult worms live in the pulmonary arteries and heart. Dogs are the main host; cats and ferrets are infected less often. Many infected dogs show no signs until the disease is advanced.',
 'Bite of a mosquito carrying infective larvae. The disease is not passed directly between dogs.',
 'Year-round preventive medication, mosquito control and regular antigen testing.',
 'unreviewed'),
('African swine fever', 'virus', 'African swine fever virus', NULL, 0, 1,
 'Highly contagious, often fatal viral hemorrhagic disease of domestic and wild pigs. Vaccines are not yet widely available, so control relies on biosecurity and culling. It does not infect people.',
 'Direct contact, contaminated feed, pork products and equipment, and soft ticks (Ornithodoros) in some regions. The virus is very stable in meat products.',
 'Strict biosecurity, no feeding of food waste to pigs, control of the movement of pigs and pork products, rapid reporting and culling of affected herds.',
 'unreviewed'),
('Anthrax', 'bacteria', 'Bacillus anthracis', NULL, 1, 1,
 'Acute, often fatal bacterial disease of herbivores caused by the spore-forming Bacillus anthracis. Livestock often die suddenly. People can be infected through contact with infected animals or contaminated animal products.',
 'Ingestion or inhalation of hardy spores from contaminated soil, feed or water; spores can survive in soil for very long periods. Carcasses must not be opened, because exposure to air promotes spore formation.',
 'Annual vaccination in endemic areas, correct disposal of carcasses, quarantine and reporting to the authorities.',
 'unreviewed'),
('Chytridiomycosis', 'fungus', 'Batrachochytrium dendrobatidis', NULL, 0, 1,
 'Fungal skin disease of amphibians that has contributed to the decline and extinction of amphibian species worldwide. The fungus infects keratinized skin and disrupts water and electrolyte balance.',
 'Motile zoospores spread through water and by contact with infected amphibians, contaminated soil or equipment, and the wildlife trade.',
 'Quarantine and testing of amphibians, biosecurity and disinfection of equipment, and restrictions on moving infected animals.',
 'unreviewed'),
('Sea turtle fibropapillomatosis', 'virus', 'Chelonid alphaherpesvirus 5 (ChHV5), associated', NULL, 0, 0,
 'Tumor disease of sea turtles, especially green turtles, with fibropapillomas on the skin, eyes and mouth and, in some animals, internal organs. It is associated with chelonid alphaherpesvirus 5; environmental cofactors are suspected.',
 'Not fully understood. The herpesvirus is thought to spread between turtles, and infection does not always lead to tumors.',
 'No specific prevention. Rescue centers monitor affected turtles and surgically remove tumors; protecting habitat and water quality is considered important.',
 'unreviewed'),
('Elephant endotheliotropic herpesvirus hemorrhagic disease', 'virus', 'Elephant endotheliotropic herpesviruses (EEHV1A, EEHV1B and others)', NULL, 0, 0,
 'Rapid, often fatal hemorrhagic disease caused by elephant endotheliotropic herpesviruses. It mainly affects young Asian elephants (most cases are in calves roughly 1 to 5 years old), while adults commonly carry the virus without signs.',
 'Adults carry latent infections and may shed virus in secretions that infect susceptible calves. Details are still under study.',
 'Regular PCR monitoring of the blood of young elephants, early treatment and intensive supportive care. Vaccines are experimental.',
 'unreviewed');

INSERT INTO disease_sources (disease_id, source_id)
SELECT d.id, s.id
FROM (
    SELECT 'Rabies' AS disease, 'WOAH - Rabies' AS source
    UNION ALL SELECT 'Rabies', 'WHO - Rabies fact sheet'
    UNION ALL SELECT 'Canine parvovirus enteritis', 'Merck Veterinary Manual - Canine parvovirus'
    UNION ALL SELECT 'Canine parvovirus enteritis', 'WSAVA Vaccination Guidelines'
    UNION ALL SELECT 'Canine distemper', 'Merck Veterinary Manual - Canine distemper'
    UNION ALL SELECT 'Canine distemper', 'WSAVA Vaccination Guidelines'
    UNION ALL SELECT 'Feline panleukopenia', 'Merck Veterinary Manual - Feline panleukopenia'
    UNION ALL SELECT 'Feline panleukopenia', 'WSAVA Vaccination Guidelines'
    UNION ALL SELECT 'Feline respiratory disease complex', 'Merck Veterinary Manual - Feline respiratory disease complex'
    UNION ALL SELECT 'Feline respiratory disease complex', 'WSAVA Vaccination Guidelines'
    UNION ALL SELECT 'Foot-and-mouth disease', 'WOAH - Foot and mouth disease'
    UNION ALL SELECT 'Bovine tuberculosis', 'WOAH - Bovine tuberculosis'
    UNION ALL SELECT 'Brucellosis', 'WOAH - Brucellosis'
    UNION ALL SELECT 'Brucellosis', 'WHO - Brucellosis fact sheet'
    UNION ALL SELECT 'Mastitis', 'Merck Veterinary Manual - Mastitis in cattle'
    UNION ALL SELECT 'Equine colic', 'Merck Veterinary Manual - Colic in horses'
    UNION ALL SELECT 'Highly pathogenic avian influenza', 'WOAH - Avian influenza'
    UNION ALL SELECT 'Myxomatosis', 'WOAH - Myxomatosis'
    UNION ALL SELECT 'Leptospirosis', 'Merck Veterinary Manual - Leptospirosis in animals'
    UNION ALL SELECT 'Heartworm disease', 'Merck Veterinary Manual - Heartworm disease in dogs, cats, and ferrets'
    UNION ALL SELECT 'Heartworm disease', 'American Heartworm Society'
    UNION ALL SELECT 'African swine fever', 'WOAH - African swine fever'
    UNION ALL SELECT 'Anthrax', 'WOAH - Anthrax'
    UNION ALL SELECT 'Chytridiomycosis', 'WOAH - Chytridiomycosis (Batrachochytrium dendrobatidis)'
    UNION ALL SELECT 'Sea turtle fibropapillomatosis', 'USGS - Sea turtle diseases'
    UNION ALL SELECT 'Elephant endotheliotropic herpesvirus hemorrhagic disease', 'Long SY, Latimer EM, Hayward GS (2016). Review of Elephant Endotheliotropic Herpesviruses and Acute Hemorrhagic Disease. ILAR J'
) x
JOIN diseases d ON d.disease_name = x.disease
JOIN sources s ON s.title = x.source;

INSERT INTO disease_sources (disease_id, source_id)
SELECT d.id, s.id FROM diseases d CROSS JOIN sources s
WHERE s.title = 'Human Disease Ontology (DO)' AND d.disease_ontology_id IS NOT NULL;

-- Symptoms of each disease
INSERT INTO disease_symptoms (disease_id, symptom_id)
SELECT d.id, s.id
FROM (
    SELECT 'Rabies' AS disease, 'behavior change' AS symptom
    UNION ALL SELECT 'Rabies', 'aggression'
    UNION ALL SELECT 'Rabies', 'excessive salivation'
    UNION ALL SELECT 'Rabies', 'paralysis'
    UNION ALL SELECT 'Rabies', 'seizures'
    UNION ALL SELECT 'Canine parvovirus enteritis', 'vomiting'
    UNION ALL SELECT 'Canine parvovirus enteritis', 'bloody diarrhea'
    UNION ALL SELECT 'Canine parvovirus enteritis', 'lethargy'
    UNION ALL SELECT 'Canine parvovirus enteritis', 'loss of appetite'
    UNION ALL SELECT 'Canine parvovirus enteritis', 'dehydration'
    UNION ALL SELECT 'Canine parvovirus enteritis', 'fever'
    UNION ALL SELECT 'Canine distemper', 'fever'
    UNION ALL SELECT 'Canine distemper', 'nasal discharge'
    UNION ALL SELECT 'Canine distemper', 'eye discharge'
    UNION ALL SELECT 'Canine distemper', 'cough'
    UNION ALL SELECT 'Canine distemper', 'diarrhea'
    UNION ALL SELECT 'Canine distemper', 'vomiting'
    UNION ALL SELECT 'Canine distemper', 'loss of appetite'
    UNION ALL SELECT 'Canine distemper', 'seizures'
    UNION ALL SELECT 'Canine distemper', 'lethargy'
    UNION ALL SELECT 'Feline panleukopenia', 'fever'
    UNION ALL SELECT 'Feline panleukopenia', 'vomiting'
    UNION ALL SELECT 'Feline panleukopenia', 'diarrhea'
    UNION ALL SELECT 'Feline panleukopenia', 'lethargy'
    UNION ALL SELECT 'Feline panleukopenia', 'loss of appetite'
    UNION ALL SELECT 'Feline panleukopenia', 'dehydration'
    UNION ALL SELECT 'Feline respiratory disease complex', 'sneezing'
    UNION ALL SELECT 'Feline respiratory disease complex', 'nasal discharge'
    UNION ALL SELECT 'Feline respiratory disease complex', 'eye discharge'
    UNION ALL SELECT 'Feline respiratory disease complex', 'conjunctivitis'
    UNION ALL SELECT 'Feline respiratory disease complex', 'fever'
    UNION ALL SELECT 'Feline respiratory disease complex', 'loss of appetite'
    UNION ALL SELECT 'Feline respiratory disease complex', 'excessive salivation'
    UNION ALL SELECT 'Foot-and-mouth disease', 'fever'
    UNION ALL SELECT 'Foot-and-mouth disease', 'oral blisters or ulcers'
    UNION ALL SELECT 'Foot-and-mouth disease', 'excessive salivation'
    UNION ALL SELECT 'Foot-and-mouth disease', 'lameness'
    UNION ALL SELECT 'Foot-and-mouth disease', 'reduced milk production'
    UNION ALL SELECT 'Foot-and-mouth disease', 'loss of appetite'
    UNION ALL SELECT 'Bovine tuberculosis', 'cough'
    UNION ALL SELECT 'Bovine tuberculosis', 'weight loss'
    UNION ALL SELECT 'Bovine tuberculosis', 'fever'
    UNION ALL SELECT 'Bovine tuberculosis', 'lethargy'
    UNION ALL SELECT 'Brucellosis', 'abortion'
    UNION ALL SELECT 'Brucellosis', 'infertility'
    UNION ALL SELECT 'Brucellosis', 'testicular swelling'
    UNION ALL SELECT 'Mastitis', 'swollen udder'
    UNION ALL SELECT 'Mastitis', 'abnormal milk'
    UNION ALL SELECT 'Mastitis', 'reduced milk production'
    UNION ALL SELECT 'Mastitis', 'fever'
    UNION ALL SELECT 'Mastitis', 'loss of appetite'
    UNION ALL SELECT 'Equine colic', 'abdominal pain'
    UNION ALL SELECT 'Equine colic', 'restlessness and rolling'
    UNION ALL SELECT 'Equine colic', 'sweating'
    UNION ALL SELECT 'Equine colic', 'loss of appetite'
    UNION ALL SELECT 'Equine colic', 'reduced manure output'
    UNION ALL SELECT 'Equine colic', 'abdominal distension'
    UNION ALL SELECT 'Highly pathogenic avian influenza', 'sudden death'
    UNION ALL SELECT 'Highly pathogenic avian influenza', 'difficulty breathing'
    UNION ALL SELECT 'Highly pathogenic avian influenza', 'swelling of head or face'
    UNION ALL SELECT 'Highly pathogenic avian influenza', 'cyanosis'
    UNION ALL SELECT 'Highly pathogenic avian influenza', 'decreased egg production'
    UNION ALL SELECT 'Highly pathogenic avian influenza', 'diarrhea'
    UNION ALL SELECT 'Myxomatosis', 'swelling of head or face'
    UNION ALL SELECT 'Myxomatosis', 'eye discharge'
    UNION ALL SELECT 'Myxomatosis', 'conjunctivitis'
    UNION ALL SELECT 'Myxomatosis', 'skin lesions'
    UNION ALL SELECT 'Myxomatosis', 'lethargy'
    UNION ALL SELECT 'Myxomatosis', 'loss of appetite'
    UNION ALL SELECT 'Myxomatosis', 'fever'
    UNION ALL SELECT 'Leptospirosis', 'fever'
    UNION ALL SELECT 'Leptospirosis', 'vomiting'
    UNION ALL SELECT 'Leptospirosis', 'jaundice'
    UNION ALL SELECT 'Leptospirosis', 'increased thirst'
    UNION ALL SELECT 'Leptospirosis', 'lethargy'
    UNION ALL SELECT 'Leptospirosis', 'loss of appetite'
    UNION ALL SELECT 'Leptospirosis', 'abortion'
    UNION ALL SELECT 'Heartworm disease', 'cough'
    UNION ALL SELECT 'Heartworm disease', 'exercise intolerance'
    UNION ALL SELECT 'Heartworm disease', 'difficulty breathing'
    UNION ALL SELECT 'Heartworm disease', 'weight loss'
    UNION ALL SELECT 'Heartworm disease', 'lethargy'
    UNION ALL SELECT 'African swine fever', 'fever'
    UNION ALL SELECT 'African swine fever', 'skin redness'
    UNION ALL SELECT 'African swine fever', 'hemorrhages'
    UNION ALL SELECT 'African swine fever', 'loss of appetite'
    UNION ALL SELECT 'African swine fever', 'lethargy'
    UNION ALL SELECT 'African swine fever', 'sudden death'
    UNION ALL SELECT 'Anthrax', 'sudden death'
    UNION ALL SELECT 'Anthrax', 'fever'
    UNION ALL SELECT 'Anthrax', 'hemorrhages'
    UNION ALL SELECT 'Chytridiomycosis', 'lethargy'
    UNION ALL SELECT 'Chytridiomycosis', 'loss of appetite'
    UNION ALL SELECT 'Chytridiomycosis', 'abnormal skin shedding'
    UNION ALL SELECT 'Sea turtle fibropapillomatosis', 'skin growths (tumors)'
    UNION ALL SELECT 'Sea turtle fibropapillomatosis', 'impaired vision'
    UNION ALL SELECT 'Sea turtle fibropapillomatosis', 'weight loss'
    UNION ALL SELECT 'Elephant endotheliotropic herpesvirus hemorrhagic disease', 'lethargy'
    UNION ALL SELECT 'Elephant endotheliotropic herpesvirus hemorrhagic disease', 'loss of appetite'
    UNION ALL SELECT 'Elephant endotheliotropic herpesvirus hemorrhagic disease', 'swelling of head or face'
    UNION ALL SELECT 'Elephant endotheliotropic herpesvirus hemorrhagic disease', 'cyanosis'
    UNION ALL SELECT 'Elephant endotheliotropic herpesvirus hemorrhagic disease', 'hemorrhages'
    UNION ALL SELECT 'Elephant endotheliotropic herpesvirus hemorrhagic disease', 'sudden death'
) x
JOIN diseases d ON d.disease_name = x.disease
JOIN symptoms s ON s.symptom_name = x.symptom;

-- Which species each disease affects (with species-specific notes)
INSERT INTO animal_diseases (animal_id, disease_id, notes)
SELECT a.id, d.id, x.notes
FROM (
    SELECT 'Dog' AS animal, 'Rabies' AS disease, 'Dog-mediated rabies causes the large majority of human rabies deaths worldwide.' AS notes
    UNION ALL SELECT 'Cat', 'Rabies', 'All mammals are susceptible.'
    UNION ALL SELECT 'Cattle', 'Rabies', 'All mammals are susceptible.'
    UNION ALL SELECT 'Horse', 'Rabies', 'All mammals are susceptible.'
    UNION ALL SELECT 'Sheep', 'Rabies', 'All mammals are susceptible.'
    UNION ALL SELECT 'Goat', 'Rabies', 'All mammals are susceptible.'
    UNION ALL SELECT 'Domestic Pig', 'Rabies', 'All mammals are susceptible.'
    UNION ALL SELECT 'Dog', 'Canine parvovirus enteritis', 'Most severe in puppies from about 6 weeks to 6 months of age and in unvaccinated dogs.'
    UNION ALL SELECT 'Dog', 'Canine distemper', 'The virus also affects ferrets and many wild carnivores.'
    UNION ALL SELECT 'Red Panda', 'Canine distemper', 'Reported in captive red pandas; the virus infects many carnivore species.'
    UNION ALL SELECT 'Cat', 'Feline panleukopenia', 'Most severe in kittens under one year of age.'
    UNION ALL SELECT 'Cat', 'Feline respiratory disease complex', NULL
    UNION ALL SELECT 'Cattle', 'Foot-and-mouth disease', NULL
    UNION ALL SELECT 'Domestic Pig', 'Foot-and-mouth disease', 'Pigs excrete large amounts of airborne virus and act as amplifying hosts.'
    UNION ALL SELECT 'Sheep', 'Foot-and-mouth disease', 'Often mild or unnoticed in sheep and goats, which can silently carry the virus.'
    UNION ALL SELECT 'Goat', 'Foot-and-mouth disease', 'Often mild or unnoticed in sheep and goats, which can silently carry the virus.'
    UNION ALL SELECT 'Cattle', 'Bovine tuberculosis', 'Cattle are the main maintenance host of Mycobacterium bovis.'
    UNION ALL SELECT 'Goat', 'Bovine tuberculosis', 'Goats can also be infected.'
    UNION ALL SELECT 'Cattle', 'Brucellosis', 'Mainly Brucella abortus.'
    UNION ALL SELECT 'Sheep', 'Brucellosis', 'Mainly Brucella melitensis; Brucella ovis causes epididymitis in rams.'
    UNION ALL SELECT 'Goat', 'Brucellosis', 'Mainly Brucella melitensis.'
    UNION ALL SELECT 'Domestic Pig', 'Brucellosis', 'Brucella suis.'
    UNION ALL SELECT 'Dog', 'Brucellosis', 'Brucella canis.'
    UNION ALL SELECT 'Cattle', 'Mastitis', 'Most important in dairy herds.'
    UNION ALL SELECT 'Sheep', 'Mastitis', 'Also occurs in lactating ewes.'
    UNION ALL SELECT 'Goat', 'Mastitis', 'Also occurs in lactating does.'
    UNION ALL SELECT 'Horse', 'Equine colic', NULL
    UNION ALL SELECT 'Chicken', 'Highly pathogenic avian influenza', 'Very high mortality in chickens.'
    UNION ALL SELECT 'Bald Eagle', 'Highly pathogenic avian influenza', 'Fatal infections have been reported in wild raptors.'
    UNION ALL SELECT 'Rabbit', 'Myxomatosis', 'European rabbits are highly susceptible.'
    UNION ALL SELECT 'Dog', 'Leptospirosis', 'Kidney and liver disease. Vaccines protect against only some serovars.'
    UNION ALL SELECT 'Cattle', 'Leptospirosis', 'Abortion and reduced milk yield.'
    UNION ALL SELECT 'Domestic Pig', 'Leptospirosis', 'Reproductive failure (abortion and stillbirth).'
    UNION ALL SELECT 'Horse', 'Leptospirosis', 'A recognized cause of recurrent uveitis (moon blindness).'
    UNION ALL SELECT 'Dog', 'Heartworm disease', 'Dogs are the main host.'
    UNION ALL SELECT 'Cat', 'Heartworm disease', 'Cats are atypical hosts: a few worms can cause severe disease, and signs can be mistaken for asthma.'
    UNION ALL SELECT 'Domestic Pig', 'African swine fever', NULL
    UNION ALL SELECT 'Cattle', 'Anthrax', NULL
    UNION ALL SELECT 'Sheep', 'Anthrax', NULL
    UNION ALL SELECT 'Goat', 'Anthrax', NULL
    UNION ALL SELECT 'Horse', 'Anthrax', NULL
    UNION ALL SELECT 'Poison Dart Frog', 'Chytridiomycosis', 'Dendrobatid frogs are susceptible; susceptibility varies between species.'
    UNION ALL SELECT 'Green Sea Turtle', 'Sea turtle fibropapillomatosis', NULL
    UNION ALL SELECT 'Asian Elephant', 'Elephant endotheliotropic herpesvirus hemorrhagic disease', 'Young Asian elephants are most often affected.'
) x
JOIN animals a ON a.common_name = x.animal
JOIN diseases d ON d.disease_name = x.disease;

-- Which body parts each disease affects
INSERT INTO disease_body_parts (disease_id, body_part_id, notes)
SELECT d.id, bp.id, x.notes
FROM (
    SELECT 'Rabies' AS disease, 'Brain' AS part, 'Encephalitis causes the behavioral and neurological signs.' AS notes
    UNION ALL SELECT 'Rabies', 'Spinal cord', NULL
    UNION ALL SELECT 'Canine parvovirus enteritis', 'Small intestine', 'Destruction of the intestinal lining causes vomiting and bloody diarrhea.'
    UNION ALL SELECT 'Canine parvovirus enteritis', 'Bone marrow', 'Fall in white blood cell numbers.'
    UNION ALL SELECT 'Canine parvovirus enteritis', 'Heart', 'Myocarditis in very young puppies.'
    UNION ALL SELECT 'Canine distemper', 'Lung', 'Respiratory discharge and pneumonia.'
    UNION ALL SELECT 'Canine distemper', 'Small intestine', 'Diarrhea and loss of appetite.'
    UNION ALL SELECT 'Canine distemper', 'Brain', 'Neurological signs can appear weeks or months after the acute illness.'
    UNION ALL SELECT 'Canine distemper', 'Eye', 'Eye discharge.'
    UNION ALL SELECT 'Canine distemper', 'Skin', 'Footpad and nasal hyperkeratosis in some survivors.'
    UNION ALL SELECT 'Feline panleukopenia', 'Small intestine', NULL
    UNION ALL SELECT 'Feline panleukopenia', 'Bone marrow', 'Fall in white blood cell numbers.'
    UNION ALL SELECT 'Feline panleukopenia', 'Brain', 'Cerebellar damage in kittens infected in the womb or in the first weeks of life.'
    UNION ALL SELECT 'Feline respiratory disease complex', 'Nose', NULL
    UNION ALL SELECT 'Feline respiratory disease complex', 'Eye', 'Conjunctivitis.'
    UNION ALL SELECT 'Feline respiratory disease complex', 'Mouth', 'Calicivirus often causes oral ulcers.'
    UNION ALL SELECT 'Feline respiratory disease complex', 'Lung', 'Some calicivirus strains cause pneumonia.'
    UNION ALL SELECT 'Foot-and-mouth disease', 'Mouth', 'Blisters and erosions on the tongue and oral lining.'
    UNION ALL SELECT 'Foot-and-mouth disease', 'Foot', 'Blisters at the coronary band and between the digits cause lameness.'
    UNION ALL SELECT 'Foot-and-mouth disease', 'Mammary gland', 'Blisters on the teats.'
    UNION ALL SELECT 'Bovine tuberculosis', 'Lung', NULL
    UNION ALL SELECT 'Bovine tuberculosis', 'Lymph node', 'Tubercles form in lymph nodes, especially those draining the lungs.'
    UNION ALL SELECT 'Brucellosis', 'Uterus', 'Placentitis leads to abortion.'
    UNION ALL SELECT 'Brucellosis', 'Testis', 'Orchitis and epididymitis in males.'
    UNION ALL SELECT 'Mastitis', 'Mammary gland', NULL
    UNION ALL SELECT 'Equine colic', 'Stomach', 'Gastric dilatation can be fatal.'
    UNION ALL SELECT 'Equine colic', 'Small intestine', NULL
    UNION ALL SELECT 'Equine colic', 'Large intestine', 'Impaction and displacement of the large colon are common.'
    UNION ALL SELECT 'Highly pathogenic avian influenza', 'Lung', NULL
    UNION ALL SELECT 'Highly pathogenic avian influenza', 'Brain', 'Neurological signs occur in some species.'
    UNION ALL SELECT 'Myxomatosis', 'Skin', 'Swollen skin and nodules, especially around the face and genitals.'
    UNION ALL SELECT 'Myxomatosis', 'Eye', 'Swollen eyelids and discharge.'
    UNION ALL SELECT 'Leptospirosis', 'Kidney', 'Bacteria persist in the kidney tubules and are shed in urine.'
    UNION ALL SELECT 'Leptospirosis', 'Liver', NULL
    UNION ALL SELECT 'Heartworm disease', 'Heart', 'Adult worms can reach the right side of the heart in heavy infections.'
    UNION ALL SELECT 'Heartworm disease', 'Lung', 'Inflammation of the lung vessels and lung tissue causes cough.'
    UNION ALL SELECT 'Heartworm disease', 'Blood vessel', 'Adult worms live mainly in the pulmonary arteries.'
    UNION ALL SELECT 'African swine fever', 'Spleen', NULL
    UNION ALL SELECT 'African swine fever', 'Lymph node', NULL
    UNION ALL SELECT 'African swine fever', 'Skin', 'Skin redness and hemorrhages.'
    UNION ALL SELECT 'Anthrax', 'Spleen', 'An enlarged spleen is typical, but carcasses of suspected anthrax must not be opened.'
    UNION ALL SELECT 'Anthrax', 'Blood', 'Septicemia.'
    UNION ALL SELECT 'Chytridiomycosis', 'Skin', 'The fungus infects keratinized skin.'
    UNION ALL SELECT 'Sea turtle fibropapillomatosis', 'Skin', 'Fibropapillomas on the skin and flippers.'
    UNION ALL SELECT 'Sea turtle fibropapillomatosis', 'Eye', 'Tumors on the eye can impair vision.'
    UNION ALL SELECT 'Sea turtle fibropapillomatosis', 'Mouth', NULL
    UNION ALL SELECT 'Elephant endotheliotropic herpesvirus hemorrhagic disease', 'Heart', 'Hemorrhage in the heart muscle can cause cardiovascular failure.'
    UNION ALL SELECT 'Elephant endotheliotropic herpesvirus hemorrhagic disease', 'Liver', NULL
    UNION ALL SELECT 'Elephant endotheliotropic herpesvirus hemorrhagic disease', 'Blood vessel', 'The virus infects the cells lining the blood vessels.'
) x
JOIN diseases d ON d.disease_name = x.disease
JOIN body_parts bp ON bp.part_name = x.part;
