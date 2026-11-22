-- Feeding guide: sample data
--
-- IMPORTANT: this is STARTER data. The numbers were taken from the sources listed in the
-- `sources` table (see each record), but the plans and example rations are NOT reviewed by
-- veterinarians or animal nutritionists. Every record is marked review_status = 'unreviewed'.
-- Feeding amounts are general starting points, not a prescription: individual animals differ
-- (health, age, pregnancy, work, climate). Ask a veterinarian or a livestock nutritionist
-- (for example at the Faculty of Veterinary Medicine and Animal Science, University of
-- Peradeniya, or the Department of Animal Production and Health) to review and correct them.
--
-- Requires the animals from seed.sql (matched by common name).

USE biodiversity_system;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Sources
-- ---------------------------------------------------------------------------
INSERT INTO sources (title, publisher, url, license, source_type) VALUES
('MSD Veterinary Manual - Daily maintenance energy requirements for dogs and cats', 'Merck & Co., Inc.', 'https://www.msdvetmanual.com/multimedia/table/daily-maintenance-energy-requirements-for-dogs-and-cats', 'Copyright Merck & Co., Inc. (link only)', 'manual'),
('WSAVA Global Nutrition Guidelines', 'World Small Animal Veterinary Association', 'https://wsava.org/global-guidelines/global-nutrition-guidelines/', 'Copyright WSAVA (link only)', 'guideline'),
('Royal Canin Academy - Calculating the energy content of pet food', 'Royal Canin', 'https://academy.royalcanin.com/en/veterinary/calculating-the-energy-content-of-pet-food', 'Copyright Royal Canin (link only)', 'guideline'),
('USDA FoodData Central', 'U.S. Department of Agriculture', 'https://fdc.nal.usda.gov/', 'Public domain (CC0 1.0)', 'database'),
('A Nutritional Investigation of Major Feed Types and Feed Rations Used in Medium-Scale Dairy Production Systems in Sri Lanka (Animals 2022, 12(18):2391)', 'MDPI Animals (via PubMed Central)', 'https://pmc.ncbi.nlm.nih.gov/articles/PMC9495045/', 'CC BY 4.0 (open access; see article)', 'article'),
('Effect of TMR Briquettes on Milk Production, Nutrient Digestibility, and Manure Excretions of Dairy Cows in the Dry Zone of Sri Lanka (Animals 2022)', 'MDPI Animals (via PubMed Central)', 'https://pmc.ncbi.nlm.nih.gov/articles/PMC8996848/', 'CC BY 4.0 (open access; see article)', 'article'),
('USDA AMS National Organic Program - Dry matter demand and intake', 'U.S. Department of Agriculture, Agricultural Marketing Service', 'https://www.ams.usda.gov/node/5699', 'Public domain (U.S. Government work)', 'guideline'),
('Alberta Agriculture - How much feed will my cow eat?', 'Government of Alberta', 'https://www1.agric.gov.ab.ca/$department/deptdocs.nsf/all/faq7811', 'Copyright Government of Alberta (link only)', 'guideline'),
('Merck Veterinary Manual - Nutrition of goats', 'Merck & Co., Inc.', 'https://www.merckvetmanual.com/management-and-nutrition/preventative-health-care-and-husbandry-of-goats/nutrition-of-goats', 'Copyright Merck & Co., Inc. (link only)', 'manual'),
('Merck Veterinary Manual - Nutrition of sheep', 'Merck & Co., Inc.', 'https://www.merckvetmanual.com/management-and-nutrition/preventative-health-care-and-husbandry-of-sheep/nutrition-of-sheep', 'Copyright Merck & Co., Inc. (link only)', 'manual'),
('AHDB - Feeding growing and finishing pigs', 'Agriculture and Horticulture Development Board', 'https://ahdb.org.uk/knowledge-library/feeding-growing-and-finishing-pigs', 'Copyright AHDB (link only)', 'guideline'),
('Merck Veterinary Manual - Feeding levels and practices in pigs', 'Merck & Co., Inc.', 'https://www.merckvetmanual.com/management-and-nutrition/nutrition-pigs/feeding-levels-and-practices-in-pigs', 'Copyright Merck & Co., Inc. (link only)', 'manual'),
('PoultryHub - Nutrient requirements of egg laying chickens', 'PoultryHub (Poultry CRC)', 'https://www.poultryhub.org/all-about-poultry/nutrition/nutrient-requirements-of-egg-laying-chickens', 'Copyright PoultryHub (link only)', 'guideline'),
('University of Minnesota Extension - Hay in horse diets', 'University of Minnesota Extension', 'https://extension.umn.edu/horse-nutrition/hay-horse-diets', 'Copyright Regents of the University of Minnesota (link only)', 'guideline'),
('MSD Veterinary Manual - Nutritional requirements of horses', 'Merck & Co., Inc.', 'https://www.msdvetmanual.com/management-and-nutrition/nutrition-horses/nutritional-requirements-of-horses', 'Copyright Merck & Co., Inc. (link only)', 'manual'),
('Merck Veterinary Manual - Diet for rabbits (pet owner version)', 'Merck & Co., Inc.', 'https://www.merckvetmanual.com/all-other-pets/rabbits/diet-for-rabbits', 'Copyright Merck & Co., Inc. (link only)', 'manual'),
('Scottish Government - Pet rabbit welfare guidance', 'Scottish Government', 'https://www.gov.scot/publications/pet-rabbit-welfare-guidance/pages/5/', 'Open Government Licence v3.0', 'guideline'),
('ASPCA - People foods to avoid feeding your pets', 'American Society for the Prevention of Cruelty to Animals', 'https://www.aspca.org/pet-care/aspca-poison-control/people-foods-avoid-feeding-your-pets', 'Copyright ASPCA (link only)', 'guideline');

-- ---------------------------------------------------------------------------
-- Feed types
-- Nutrient values: Sri Lankan forages and concentrate from PMC9495045 (tables 8 and 9);
-- pet food energy ranges from Royal Canin Academy; cooked rice and chicken from USDA FoodData Central.
-- Local (Sinhala/Tamil) names are left empty on purpose: they should be added by a native speaker.
-- ---------------------------------------------------------------------------
INSERT INTO feeds
(feed_name, feed_category, scientific_name, dry_matter_percent, crude_protein_percent, me_mj_per_kg_dm, energy_kcal_per_kg, description, availability_note, cautions, review_status)
VALUES
('Guinea grass (fresh)', 'green_forage', 'Panicum maximum', 23.60, 8.00, 7.50, NULL,
 'A tall tropical fodder grass cut and carried to stall-fed cattle. Low in protein and high in fiber, so it needs a protein source (for example Gliricidia or a concentrate) for productive animals.',
 'One of the two main fodder grasses (with Hybrid Napier CO-3) on Sri Lankan dairy farms studied in the Kurunegala district.',
 'Cut before it gets coarse and stemmy; mature grass is eaten less and digested worse.', 'unreviewed'),
('Hybrid Napier CO-3 grass (fresh)', 'green_forage', 'Pennisetum purpureum x Pennisetum americanum', 18.80, 8.80, 8.00, NULL,
 'A high-yielding hybrid Napier fodder grass. Like other tropical grasses it is low in protein; its high water content means a large weight of fresh grass is needed to supply the dry matter.',
 'Widely used cut-and-carry fodder on Sri Lankan dairy farms (Kurunegala study). Cutting every 45 to 60 days was typical.',
 'Very watery (about 19% dry matter): weigh fresh grass and convert with the dry-matter figure.', 'unreviewed'),
('Gliricidia leaves (fresh)', 'green_forage', 'Gliricidia sepium', 26.00, 17.70, 10.00, NULL,
 'A leguminous tree whose leaves are a protein-rich supplement to grass for ruminants. Its crude protein (about 18% of dry matter) is about twice that of fodder grasses.',
 'Grown as a fodder tree and fence plant in Sri Lanka, but not abundant in every district.',
 'For ruminants only (cattle, goats, sheep): introduce gradually and keep it to a minority of the ration (about a quarter of the dry matter at most). Do not feed to horses, dogs or other non-ruminants.', 'unreviewed'),
('Maize stover (mature)', 'dry_forage', 'Zea mays', 27.70, 8.70, 8.90, NULL,
 'The stalks and leaves left after harvesting maize cobs. A fibrous filler forage used on some dairy farms.',
 'A crop residue available where maize is grown.',
 'Mature stover is fibrous and low in protein; do not use it as the only feed for productive animals.', 'unreviewed'),
('Maize silage', 'green_forage', 'Zea mays', 29.10, 7.50, 11.00, NULL,
 'Chopped whole-plant maize that has been fermented in an airtight store. Provides more energy than tropical grasses.',
 'Used on some larger Sri Lankan dairy farms; needs a silo or sealed bags.',
 'Spoiled or moldy silage must be thrown away.', 'unreviewed'),
('Formulated dairy cattle feed (concentrate)', 'concentrate', NULL, 89.00, 15.70, 12.80, NULL,
 'A manufactured concentrate mixed for dairy cattle. It supplies energy and protein that tropical grasses lack. Composition differs between brands: use the analysis printed on the bag where it differs from the values here.',
 'Sold by commercial feed mills in Sri Lanka; used on all nine farms in the Kurunegala study.',
 'Increase concentrate gradually. Large sudden increases in grain or concentrate can cause rumen acidosis. Not for horses, pigs or poultry.', 'unreviewed'),
('Coconut poonac (coconut cake)', 'concentrate', 'Cocos nucifera', NULL, NULL, NULL, NULL,
 'The residue left after oil is pressed from dried coconut. A protein-containing ingredient used in concentrates for dairy cows in Sri Lanka. Nutrient content varies with how the oil was extracted.',
 'A by-product of the coconut oil industry in Sri Lanka.',
 'Fat and protein content vary; buy fresh, dry poonac and store it dry because it spoils and molds easily. Check a supplier analysis before using it in a ration.', 'unreviewed'),
('Soybean meal', 'concentrate', 'Glycine max', NULL, NULL, NULL, NULL,
 'A high-protein feed ingredient made from soybeans after oil extraction. Used to raise the protein level of rations when grasses are low in protein.',
 'Imported; usually bought as an ingredient in manufactured feeds.',
 'Use as an ingredient in a balanced ration, not as a single feed.', 'unreviewed'),
('Calcium mineral mixture', 'supplement', NULL, NULL, NULL, NULL, NULL,
 'A mineral supplement rich in calcium given to lactating dairy cows. All lactating cows on the nine Kurunegala farms received 60 to 90 g per cow per day.',
 'Sold by feed suppliers and agricultural shops.',
 'Follow the supplier directions; too much or too little calcium causes problems.', 'unreviewed'),
('Salt and mineral mix (free choice)', 'supplement', NULL, NULL, NULL, NULL, NULL,
 'Salt, with trace minerals suited to the area, offered free choice so animals can take what they need. Loose minerals are preferred to blocks for goats and sheep. A mature sheep takes about 9 g of salt a day.',
 'Sold by feed suppliers; choose a mix made for the species.',
 'Mineral mixes for sheep can contain copper levels unsafe for other species; use a mix made for your species.', 'unreviewed'),
('Dry complete dog food', 'complete_feed', NULL, NULL, NULL, NULL, 3800,
 'A manufactured complete kibble. Energy differs a lot between products (about 2,800 to 4,800 kcal per kg), so the default of 3,800 is only a mid-range value. Use the kcal per kg printed on your product package for an accurate amount.',
 'Imported and local brands are sold in pet shops and supermarkets.',
 'Choose a food labelled complete for the life stage (puppy or adult).', 'unreviewed'),
('Wet complete dog food', 'complete_feed', NULL, NULL, NULL, NULL, 1000,
 'A manufactured complete canned or pouch food. Energy is much lower than dry food (about 700 to 1,300 kcal per kg) because it contains a lot of water, so the amount by weight is about 4 times larger.',
 'Sold in pet shops and supermarkets.',
 'Choose a food labelled complete for the life stage. Use the kcal per kg printed on the product package for an accurate amount.', 'unreviewed'),
('Dry complete cat food', 'complete_feed', NULL, NULL, NULL, NULL, 3800,
 'A manufactured complete kibble for cats. Energy differs between products (about 2,800 to 4,800 kcal per kg); the default of 3,800 is a mid-range value. Use the kcal per kg printed on the package for an accurate amount.',
 'Sold in pet shops and supermarkets.',
 'Use a food made for cats (it supplies nutrients cats need, such as taurine). Choose kitten food for kittens.', 'unreviewed'),
('Wet complete cat food', 'complete_feed', NULL, NULL, NULL, NULL, 1000,
 'A manufactured complete canned or pouch food for cats. Energy is about 700 to 1,300 kcal per kg, so the amount by weight is about 4 times that of dry food.',
 'Sold in pet shops and supermarkets.',
 'Use a food made for cats. Use the kcal per kg printed on the package for an accurate amount.', 'unreviewed'),
('Cooked rice (plain, white or red)', 'kitchen_food', 'Oryza sativa', NULL, NULL, NULL, 1300,
 'Plain boiled rice, a common home-cooked base for dogs in Sri Lanka. White rice provides about 130 kcal per 100 g.',
 'Available in every household.',
 'Rice and chicken alone are not a complete diet. For more than a few days, ask a veterinarian or veterinary nutritionist for a balanced recipe with calcium and a vitamin-mineral supplement. Never add onion, garlic, salt, spices or cooked bones. Not for puppies or cats as the main diet.', 'unreviewed'),
('Cooked skinless chicken breast (plain)', 'kitchen_food', 'Gallus gallus', NULL, NULL, NULL, 1650,
 'Plain boiled or roasted chicken breast without skin or bones, a common protein source for home-cooked dog meals. Provides about 165 kcal per 100 g. Boiled fish without bones can be used instead, but its energy differs.',
 'Available from butchers and markets.',
 'Cook it thoroughly and never include bones, skin, salt, onion, garlic or spices. Not a complete diet by itself.', 'unreviewed'),
('Commercial layer feed', 'complete_feed', NULL, NULL, NULL, NULL, NULL,
 'A manufactured complete feed for laying hens and for growing layer pullets (starter, grower and developer feeds are matched to age). Feed the product made for the age of the birds.',
 'Sold by feed mills and agricultural shops across Sri Lanka.',
 'Keep it dry and clean; moldy feed can poison birds. Fresh water must always be available.', 'unreviewed'),
('Commercial pig grower and finisher feed', 'complete_feed', NULL, NULL, NULL, NULL, NULL,
 'A manufactured complete feed for growing and finishing pigs, usually fed ad lib in a feeder that limits waste.',
 'Sold by feed mills and agricultural shops.',
 'Keep it dry. Cereal grains and feed must be free of molds and mycotoxins.', 'unreviewed'),
('Rabbit pellets (grass-based, 15-20% fiber)', 'complete_feed', NULL, NULL, NULL, NULL, NULL,
 'Good-quality commercial pellets with 15 to 20% crude fiber and without added dried fruit or nuts. Pellets are a smaller part of the diet; grass hay should make up most of it.',
 'Sold in pet shops and agricultural shops; supply varies.',
 'Adult rabbits should not be fed alfalfa-based pellets (too much calcium over time).', 'unreviewed'),
('Grass hay', 'dry_forage', NULL, NULL, NULL, NULL, NULL,
 'Dried grass hay. For rabbits it is the main food: unlimited good-quality grass hay is recommended, making up about 85 to 90% of the diet. Hay is about 90% dry matter.',
 'Hay is not widely sold in Sri Lanka; dried cut grass (not moldy) can be used.',
 'Must be clean, dry and free of mold.', 'unreviewed'),
('Fresh leafy vegetables', 'kitchen_food', NULL, NULL, NULL, NULL, NULL,
 'Washed fresh leafy greens for rabbits, about 1 cup per 2 pounds (about 1 kg) of body weight per day.',
 'Available from markets.',
 'Introduce new vegetables slowly and check with a veterinarian before feeding unfamiliar plants.', 'unreviewed');

INSERT INTO feed_sources (feed_id, source_id)
SELECT f.id, s.id
FROM (
    SELECT 'Guinea grass (fresh)' AS feed, 'A Nutritional Investigation of Major Feed Types and Feed Rations Used in Medium-Scale Dairy Production Systems in Sri Lanka (Animals 2022, 12(18):2391)' AS source
    UNION ALL SELECT 'Hybrid Napier CO-3 grass (fresh)', 'A Nutritional Investigation of Major Feed Types and Feed Rations Used in Medium-Scale Dairy Production Systems in Sri Lanka (Animals 2022, 12(18):2391)'
    UNION ALL SELECT 'Gliricidia leaves (fresh)', 'A Nutritional Investigation of Major Feed Types and Feed Rations Used in Medium-Scale Dairy Production Systems in Sri Lanka (Animals 2022, 12(18):2391)'
    UNION ALL SELECT 'Maize stover (mature)', 'A Nutritional Investigation of Major Feed Types and Feed Rations Used in Medium-Scale Dairy Production Systems in Sri Lanka (Animals 2022, 12(18):2391)'
    UNION ALL SELECT 'Maize silage', 'A Nutritional Investigation of Major Feed Types and Feed Rations Used in Medium-Scale Dairy Production Systems in Sri Lanka (Animals 2022, 12(18):2391)'
    UNION ALL SELECT 'Formulated dairy cattle feed (concentrate)', 'A Nutritional Investigation of Major Feed Types and Feed Rations Used in Medium-Scale Dairy Production Systems in Sri Lanka (Animals 2022, 12(18):2391)'
    UNION ALL SELECT 'Calcium mineral mixture', 'A Nutritional Investigation of Major Feed Types and Feed Rations Used in Medium-Scale Dairy Production Systems in Sri Lanka (Animals 2022, 12(18):2391)'
    UNION ALL SELECT 'Coconut poonac (coconut cake)', 'Effect of TMR Briquettes on Milk Production, Nutrient Digestibility, and Manure Excretions of Dairy Cows in the Dry Zone of Sri Lanka (Animals 2022)'
    UNION ALL SELECT 'Soybean meal', 'Effect of TMR Briquettes on Milk Production, Nutrient Digestibility, and Manure Excretions of Dairy Cows in the Dry Zone of Sri Lanka (Animals 2022)'
    UNION ALL SELECT 'Salt and mineral mix (free choice)', 'Merck Veterinary Manual - Nutrition of sheep'
    UNION ALL SELECT 'Salt and mineral mix (free choice)', 'Merck Veterinary Manual - Nutrition of goats'
    UNION ALL SELECT 'Dry complete dog food', 'Royal Canin Academy - Calculating the energy content of pet food'
    UNION ALL SELECT 'Wet complete dog food', 'Royal Canin Academy - Calculating the energy content of pet food'
    UNION ALL SELECT 'Dry complete cat food', 'Royal Canin Academy - Calculating the energy content of pet food'
    UNION ALL SELECT 'Wet complete cat food', 'Royal Canin Academy - Calculating the energy content of pet food'
    UNION ALL SELECT 'Cooked rice (plain, white or red)', 'USDA FoodData Central'
    UNION ALL SELECT 'Cooked skinless chicken breast (plain)', 'USDA FoodData Central'
    UNION ALL SELECT 'Commercial layer feed', 'PoultryHub - Nutrient requirements of egg laying chickens'
    UNION ALL SELECT 'Commercial pig grower and finisher feed', 'Merck Veterinary Manual - Feeding levels and practices in pigs'
    UNION ALL SELECT 'Rabbit pellets (grass-based, 15-20% fiber)', 'Merck Veterinary Manual - Diet for rabbits (pet owner version)'
    UNION ALL SELECT 'Grass hay', 'Merck Veterinary Manual - Diet for rabbits (pet owner version)'
    UNION ALL SELECT 'Grass hay', 'USDA AMS National Organic Program - Dry matter demand and intake'
    UNION ALL SELECT 'Fresh leafy vegetables', 'Merck Veterinary Manual - Diet for rabbits (pet owner version)'
) x
JOIN feeds f ON f.feed_name = x.feed
JOIN sources s ON s.title = x.source;

-- ---------------------------------------------------------------------------
-- Feeding plans (one per animal and life stage)
-- ---------------------------------------------------------------------------
INSERT INTO feeding_plans
(animal_id, life_stage, method, amount_min, amount_max, amount_typical, min_weight_kg, max_weight_kg, meals_per_day, target_cp_min, notes, sort_order, review_status)
SELECT a.id, x.life_stage, x.method, x.amount_min, x.amount_max, x.amount_typical, x.min_w, x.max_w, x.meals, x.cp_min, x.notes, x.ord, 'unreviewed'
FROM (
    SELECT 'Dog' AS animal, 'Puppy (under 4 months)' AS life_stage, 'energy' AS method,
           3.00 AS amount_min, 3.00 AS amount_max, 3.00 AS amount_typical, 0.5 AS min_w, 25.0 AS max_w, 4 AS meals, CAST(NULL AS DECIMAL(5,2)) AS cp_min,
           'Energy need = 3 x RER (RER = 70 x body weight in kg to the power 0.75), from the MSD Veterinary Manual table. Puppies need a complete puppy (growth) food in 3 to 4 small meals. This is only a starting point: weigh the puppy every week or two and ask a veterinarian about growth. Do not use home-cooked rice and chicken as the main diet for puppies.' AS notes, 1 AS ord
    UNION ALL SELECT 'Dog', 'Puppy (4 months to adult)', 'energy', 2.00, 2.00, 2.00, 1.0, 50.0, 3, NULL,
           'Energy need = 2 x RER (RER = 70 x body weight in kg to the power 0.75), from the MSD Veterinary Manual table. Feed a complete puppy (growth) food in about 3 meals. Weigh the puppy every 2 weeks and adjust by about 10% to keep it lean and growing steadily.', 2
    UNION ALL SELECT 'Dog', 'Adult - neutered or spayed', 'energy', 1.60, 1.60, 1.60, 1.5, 90.0, 2, NULL,
           'Energy need = 1.6 x RER (RER = 70 x body weight in kg to the power 0.75), from the MSD Veterinary Manual table. A starting point only: weigh your dog every 2 to 4 weeks and change the amount by about 10% to keep an ideal body condition. Sick, pregnant, nursing and working dogs need individual advice from a veterinarian.', 3
    UNION ALL SELECT 'Dog', 'Adult - not neutered', 'energy', 1.80, 1.80, 1.80, 1.5, 90.0, 2, NULL,
           'Energy need = 1.8 x RER (RER = 70 x body weight in kg to the power 0.75), from the MSD Veterinary Manual table. A starting point only: weigh your dog every 2 to 4 weeks and adjust by about 10%.', 4
    UNION ALL SELECT 'Dog', 'Adult - prone to obesity', 'energy', 1.40, 1.40, 1.40, 1.5, 90.0, 2, NULL,
           'Energy need = 1.4 x RER (RER = 70 x body weight in kg to the power 0.75), from the MSD Veterinary Manual table, for dogs that gain weight easily. Measure food exactly, limit treats, and weigh your dog every 2 weeks. A veterinarian should design a plan for an overweight dog.', 5
    UNION ALL SELECT 'Cat', 'Kitten', 'energy', 2.50, 2.50, 2.50, 0.1, 4.0, 4, NULL,
           'Energy need = 2.5 x RER (RER = 70 x body weight in kg to the power 0.75), from the MSD Veterinary Manual table, which notes that kittens can alternatively be fed free choice. Use a complete kitten food in 3 to 4 small meals. Cats are obligate carnivores: do not use dog food or rice-based home diets as the main diet.', 1
    UNION ALL SELECT 'Cat', 'Adult - neutered or spayed', 'energy', 1.20, 1.20, 1.20, 1.5, 10.0, 3, NULL,
           'Energy need = 1.2 x RER (RER = 70 x body weight in kg to the power 0.75), from the MSD Veterinary Manual table. A starting point only: weigh your cat every 2 to 4 weeks and change the amount by about 10%. Cats are obligate carnivores and need a complete cat food (it contains taurine); do not use dog food or rice-based home diets as the main diet.', 2
    UNION ALL SELECT 'Cat', 'Adult - not neutered', 'energy', 1.40, 1.40, 1.40, 1.5, 10.0, 3, NULL,
           'Energy need = 1.4 x RER (RER = 70 x body weight in kg to the power 0.75), from the MSD Veterinary Manual table. A starting point only: weigh your cat every 2 to 4 weeks and adjust by about 10%.', 3
    UNION ALL SELECT 'Cat', 'Adult - prone to obesity', 'energy', 1.00, 1.00, 1.00, 1.5, 10.0, 3, NULL,
           'Energy need = 1.0 x RER (RER = 70 x body weight in kg to the power 0.75), from the MSD Veterinary Manual table, for cats that gain weight easily. Measure food exactly and limit treats. A veterinarian should design a plan for an overweight cat.', 4
    UNION ALL SELECT 'Cattle', 'Dry cow or late pregnancy', 'pct_dm', 2.10, 2.30, 2.20, 250.0, 800.0, 2, 10.00,
           'In a Sri Lankan study the calculated dry-matter requirement of 450 kg dry or late-pregnant cows was 9.4 to 10.1 kg a day, about 2.1 to 2.3% of body weight. The example ration is built from forages measured on Kurunegala farms and gives about 11% crude protein (10 to 12% is needed). Fresh clean water must always be available. Adjust to body condition.', 1
    UNION ALL SELECT 'Cattle', 'Lactating dairy cow (about 10-17 litres of milk a day)', 'pct_dm', 2.50, 3.30, 3.00, 250.0, 800.0, 3, 13.50,
           'In the Sri Lankan study the calculated dry-matter requirement of 450 kg cows giving 10 to 17 litres of milk was about 11 to 15 kg a day (2.5 to 3.3% of body weight); the USDA gives 2.5 to 4.5% as the general range for dairy cows and about 3.0% as an example. The example ration gives about 13.6% crude protein, enough for mid and late lactation. Early lactation (up to about 100 days) needs 16 to 18% crude protein, so extra protein feed is needed: ask a livestock nutritionist. Lower-yielding cows need less concentrate. Spread the ration over 2 to 3 feeds and introduce changes gradually.', 2
    UNION ALL SELECT 'Cattle', 'Growing heifer', 'pct_dm', 2.50, 2.90, 2.70, 80.0, 450.0, 2, NULL,
           'USDA example: a 500 lb (227 kg) replacement heifer gaining about 1 lb a day needs dry matter of about 2.9% of body weight. The example ration is built from forages measured on Kurunegala farms. Growth targets and feed quality vary, so check growth with a veterinarian or livestock officer.', 3
    UNION ALL SELECT 'Goat', 'Adult goat - maintenance', 'pct_dm', 1.80, 2.00, 1.90, 5.0, 120.0, 2, 7.00,
           'Merck Veterinary Manual: on average goats eat 1.8 to 2.0% of body weight as dry matter, and maintenance forage should contain at least 7% crude protein. This plan is for adult goats at maintenance only. Goats in late pregnancy, lactation or growth need much more and a better ration (goats can eat up to 6.5% of body weight as dry matter): ask a livestock nutritionist. Offer clean water and loose minerals. Bucks and wethers need care with grain (see foods to avoid).', 1
    UNION ALL SELECT 'Sheep', 'Adult sheep - maintenance', 'pct_dm', 1.80, 2.00, 1.90, 8.0, 120.0, 2, 7.00,
           'Merck Veterinary Manual: on average sheep eat 1.8 to 2.0% of body weight as dry matter, and maintenance forage should contain at least 7% crude protein. This plan is for adult sheep at maintenance only; ewes in late pregnancy or lactation and growing lambs need more and a better ration. Offer clean water and a salt-mineral mix made for sheep.', 1
    UNION ALL SELECT 'Horse', 'Adult horse - maintenance or light work', 'pct_dm', 2.00, 2.50, 2.20, 150.0, 800.0, 3, NULL,
           'Horses eat about 2 to 2.5% of body weight as dry matter. Forage should supply at least half of the ration and at least 1% (1.5 to 2% is recommended) of body weight. Hay is about 90% dry matter, so use the dry-matter target to work out the weight of hay. Feed small meals through the day and always provide clean water. Horses in hard work, pregnancy, lactation or growth need individual advice.', 1
    UNION ALL SELECT 'Domestic Pig', 'Growing and finishing pig (fed ad lib)', 'table', NULL, NULL, NULL, 13.0, 110.0, 2, NULL,
           'AHDB: a 13 kg pig eats about 0.5 kg of feed a day, rising to 2.5 kg a day or more by the time a finisher reaches 110 kg; values between those weights are interpolated in a straight line. Growing pigs are usually fed ad lib, so these are typical intakes, not limits; intake changes with temperature, health and feed quality. Never feed uncooked food waste or meat products to pigs (African swine fever risk).', 1
    UNION ALL SELECT 'Chicken', 'Adult laying hen', 'fixed', 100.00, 115.00, 105.00, 1.0, 3.5, 1, NULL,
           'PoultryHub: feed intake of modern brown-egg laying hens rises to a steady 100 to 105 g a day at a body weight of about 1.7 to 1.8 kg; flocks differ with breed, temperature and egg production, and hot weather lowers intake. Always provide clean water and use a complete layer feed.', 1
    UNION ALL SELECT 'Chicken', 'Layer pullet (growing, brown-egg breed)', 'table', NULL, NULL, NULL, 0.07, 1.52, 1, NULL,
           'PoultryHub table of body weight and feed consumption during the growing period (weeks 1 to 17) of a brown-egg laying breed; values between table points are interpolated. White-egg breeds are lighter and eat somewhat less. Feed the starter, grower and developer feeds matched to age. Do not use this for broiler chickens, which are fed from the integrator or breeder feeding chart.', 2
    UNION ALL SELECT 'Rabbit', 'Adult rabbit', 'pct_asfed', 1.50, 2.50, 2.00, 0.3, 7.0, 2, NULL,
           'Unlimited good-quality grass hay should make up most of the diet (about 85 to 90%). Pellets: the Merck Veterinary Manual gives about 1/4 cup per 5 lb (2.3 kg) of body weight a day, the Scottish Government guidance gives 25 g per kg, and some welfare sources give 15 g per kg; the range here is 15 to 25 g per kg. Fresh leafy vegetables: about 1 cup per 2 lb (1 kg) a day. Adjust to body condition. Young, pregnant and nursing rabbits have different needs.', 1
) x
JOIN animals a ON a.common_name = x.animal;

-- Weight/amount tables for plans with method 'table' (grams per day, as fed)
INSERT INTO feeding_plan_points (plan_id, weight_kg, amount_g)
SELECT p.id, x.w, x.g
FROM feeding_plans p
JOIN animals a ON a.id = p.animal_id AND a.common_name = 'Domestic Pig' AND p.method = 'table'
JOIN (
    SELECT 13.0 AS w, 500.0 AS g
    UNION ALL SELECT 110.0, 2500.0
) x;

INSERT INTO feeding_plan_points (plan_id, weight_kg, amount_g)
SELECT p.id, x.w, x.g
FROM feeding_plans p
JOIN animals a ON a.id = p.animal_id AND a.common_name = 'Chicken' AND p.method = 'table'
JOIN (
    SELECT 0.070 AS w, 13.0 AS g
    UNION ALL SELECT 0.115, 20.0
    UNION ALL SELECT 0.190, 25.0
    UNION ALL SELECT 0.280, 29.0
    UNION ALL SELECT 0.385, 33.0
    UNION ALL SELECT 0.490, 37.0
    UNION ALL SELECT 0.600, 41.0
    UNION ALL SELECT 0.715, 46.0
    UNION ALL SELECT 0.815, 51.0
    UNION ALL SELECT 0.920, 56.0
    UNION ALL SELECT 1.020, 61.0
    UNION ALL SELECT 1.083, 66.0
    UNION ALL SELECT 1.190, 70.0
    UNION ALL SELECT 1.260, 73.0
    UNION ALL SELECT 1.330, 75.0
    UNION ALL SELECT 1.395, 77.0
    UNION ALL SELECT 1.520, 80.0
) x;

-- ---------------------------------------------------------------------------
-- Diet options (the feeds of each plan)
-- ---------------------------------------------------------------------------
-- Dogs: dry and wet commercial food for every life stage
INSERT INTO feeding_plan_items (plan_id, diet_name, feed_id, share_percent, fixed_min_g, fixed_max_g, notes, sort_order)
SELECT p.id, x.diet, f.id, x.share, NULL, NULL, x.notes, x.ord
FROM feeding_plans p
JOIN animals a ON a.id = p.animal_id AND a.common_name = 'Dog'
JOIN (
    SELECT 'Commercial dry food' AS diet, 'Dry complete dog food' AS feed, 100.00 AS share, 'Check the kcal per kg on the package and enter it below for a more accurate amount.' AS notes, 1 AS ord
    UNION ALL SELECT 'Commercial wet food', 'Wet complete dog food', 100.00, 'Check the kcal per kg on the package and enter it below for a more accurate amount.', 2
) x
JOIN feeds f ON f.feed_name = x.feed;

-- Dogs: home-cooked rice and chicken, adults only (not puppies)
INSERT INTO feeding_plan_items (plan_id, diet_name, feed_id, share_percent, fixed_min_g, fixed_max_g, notes, sort_order)
SELECT p.id, 'Home-cooked rice and chicken (not complete long-term)', f.id, x.share, NULL, NULL, x.notes, x.ord
FROM feeding_plans p
JOIN animals a ON a.id = p.animal_id AND a.common_name = 'Dog'
JOIN (
    SELECT 'Cooked rice (plain, white or red)' AS feed, 61.00 AS share, 'About 2 parts rice to 1 part chicken by weight (example mix).' AS notes, 3 AS ord
    UNION ALL SELECT 'Cooked skinless chicken breast (plain)', 39.00, 'Boneless and skinless, cooked without salt, oil, onion or garlic.', 4
) x
JOIN feeds f ON f.feed_name = x.feed
WHERE p.life_stage LIKE 'Adult%';

-- Cats: dry and wet commercial food for every life stage
INSERT INTO feeding_plan_items (plan_id, diet_name, feed_id, share_percent, fixed_min_g, fixed_max_g, notes, sort_order)
SELECT p.id, x.diet, f.id, x.share, NULL, NULL, x.notes, x.ord
FROM feeding_plans p
JOIN animals a ON a.id = p.animal_id AND a.common_name = 'Cat'
JOIN (
    SELECT 'Commercial dry food' AS diet, 'Dry complete cat food' AS feed, 100.00 AS share, 'Check the kcal per kg on the package and enter it below for a more accurate amount.' AS notes, 1 AS ord
    UNION ALL SELECT 'Commercial wet food', 'Wet complete cat food', 100.00, 'Check the kcal per kg on the package and enter it below for a more accurate amount.', 2
) x
JOIN feeds f ON f.feed_name = x.feed;

-- Ruminants, horses, pigs, poultry, rabbits
INSERT INTO feeding_plan_items (plan_id, diet_name, feed_id, share_percent, fixed_min_g, fixed_max_g, notes, sort_order)
SELECT p.id, x.diet, f.id, x.share, x.fmin, x.fmax, x.notes, x.ord
FROM (
    SELECT 'Cattle' AS animal, 'Dry cow or late pregnancy' AS stage, 'Forage-based ration' AS diet, 'Hybrid Napier CO-3 grass (fresh)' AS feed, 35.00 AS share, CAST(NULL AS DECIMAL(8,2)) AS fmin, CAST(NULL AS DECIMAL(8,2)) AS fmax, 'Share of the daily dry matter.' AS notes, 1 AS ord
    UNION ALL SELECT 'Cattle', 'Dry cow or late pregnancy', 'Forage-based ration', 'Guinea grass (fresh)', 35.00, NULL, NULL, 'Share of the daily dry matter.', 2
    UNION ALL SELECT 'Cattle', 'Dry cow or late pregnancy', 'Forage-based ration', 'Gliricidia leaves (fresh)', 20.00, NULL, NULL, 'Protein-rich leaves; introduce gradually.', 3
    UNION ALL SELECT 'Cattle', 'Dry cow or late pregnancy', 'Forage-based ration', 'Formulated dairy cattle feed (concentrate)', 10.00, NULL, NULL, 'Share of the daily dry matter.', 4
    UNION ALL SELECT 'Cattle', 'Dry cow or late pregnancy', 'Forage-based ration', 'Salt and mineral mix (free choice)', NULL, NULL, NULL, 'Offer free choice, with clean water always available.', 5
    UNION ALL SELECT 'Cattle', 'Lactating dairy cow (about 10-17 litres of milk a day)', 'Forage plus dairy concentrate', 'Hybrid Napier CO-3 grass (fresh)', 20.00, NULL, NULL, 'Share of the daily dry matter.', 1
    UNION ALL SELECT 'Cattle', 'Lactating dairy cow (about 10-17 litres of milk a day)', 'Forage plus dairy concentrate', 'Guinea grass (fresh)', 15.00, NULL, NULL, 'Share of the daily dry matter.', 2
    UNION ALL SELECT 'Cattle', 'Lactating dairy cow (about 10-17 litres of milk a day)', 'Forage plus dairy concentrate', 'Gliricidia leaves (fresh)', 20.00, NULL, NULL, 'Protein-rich leaves; introduce gradually.', 3
    UNION ALL SELECT 'Cattle', 'Lactating dairy cow (about 10-17 litres of milk a day)', 'Forage plus dairy concentrate', 'Formulated dairy cattle feed (concentrate)', 45.00, NULL, NULL, 'Split into 2 to 3 feeds a day; raise slowly to avoid acidosis.', 4
    UNION ALL SELECT 'Cattle', 'Lactating dairy cow (about 10-17 litres of milk a day)', 'Forage plus dairy concentrate', 'Calcium mineral mixture', NULL, 60.00, 90.00, 'All lactating cows on the study farms received 60 to 90 g per cow per day.', 5
    UNION ALL SELECT 'Cattle', 'Growing heifer', 'Forage plus concentrate', 'Hybrid Napier CO-3 grass (fresh)', 35.00, NULL, NULL, 'Share of the daily dry matter.', 1
    UNION ALL SELECT 'Cattle', 'Growing heifer', 'Forage plus concentrate', 'Guinea grass (fresh)', 30.00, NULL, NULL, 'Share of the daily dry matter.', 2
    UNION ALL SELECT 'Cattle', 'Growing heifer', 'Forage plus concentrate', 'Gliricidia leaves (fresh)', 15.00, NULL, NULL, 'Protein-rich leaves; introduce gradually.', 3
    UNION ALL SELECT 'Cattle', 'Growing heifer', 'Forage plus concentrate', 'Formulated dairy cattle feed (concentrate)', 20.00, NULL, NULL, 'Share of the daily dry matter.', 4
    UNION ALL SELECT 'Cattle', 'Growing heifer', 'Forage plus concentrate', 'Salt and mineral mix (free choice)', NULL, NULL, NULL, 'Offer free choice, with clean water always available.', 5
    UNION ALL SELECT 'Goat', 'Adult goat - maintenance', 'Grass and browse ration', 'Guinea grass (fresh)', 40.00, NULL, NULL, 'Share of the daily dry matter.', 1
    UNION ALL SELECT 'Goat', 'Adult goat - maintenance', 'Grass and browse ration', 'Hybrid Napier CO-3 grass (fresh)', 35.00, NULL, NULL, 'Share of the daily dry matter.', 2
    UNION ALL SELECT 'Goat', 'Adult goat - maintenance', 'Grass and browse ration', 'Gliricidia leaves (fresh)', 25.00, NULL, NULL, 'Protein-rich leaves; introduce gradually.', 3
    UNION ALL SELECT 'Goat', 'Adult goat - maintenance', 'Grass and browse ration', 'Salt and mineral mix (free choice)', NULL, NULL, NULL, 'Loose minerals are preferred to blocks. Offer free choice and clean water.', 4
    UNION ALL SELECT 'Sheep', 'Adult sheep - maintenance', 'Grass-based ration', 'Guinea grass (fresh)', 50.00, NULL, NULL, 'Share of the daily dry matter.', 1
    UNION ALL SELECT 'Sheep', 'Adult sheep - maintenance', 'Grass-based ration', 'Hybrid Napier CO-3 grass (fresh)', 30.00, NULL, NULL, 'Share of the daily dry matter.', 2
    UNION ALL SELECT 'Sheep', 'Adult sheep - maintenance', 'Grass-based ration', 'Gliricidia leaves (fresh)', 20.00, NULL, NULL, 'Protein-rich leaves; introduce gradually.', 3
    UNION ALL SELECT 'Sheep', 'Adult sheep - maintenance', 'Grass-based ration', 'Salt and mineral mix (free choice)', NULL, 9.00, 9.00, 'A mature sheep takes about 9 g of salt a day. Use a mix made for sheep.', 4
    UNION ALL SELECT 'Horse', 'Adult horse - maintenance or light work', 'Fresh grass', 'Guinea grass (fresh)', 100.00, NULL, NULL, 'Share of the daily dry matter. If feeding hay instead, hay is about 90% dry matter.', 1
    UNION ALL SELECT 'Horse', 'Adult horse - maintenance or light work', 'Fresh grass', 'Salt and mineral mix (free choice)', NULL, NULL, NULL, 'Offer free choice, with clean water always available.', 2
    UNION ALL SELECT 'Domestic Pig', 'Growing and finishing pig (fed ad lib)', 'Complete pig feed', 'Commercial pig grower and finisher feed', 100.00, NULL, NULL, 'Offer ad lib and keep clean water available.', 1
    UNION ALL SELECT 'Chicken', 'Adult laying hen', 'Complete layer feed', 'Commercial layer feed', 100.00, NULL, NULL, 'Per hen per day.', 1
    UNION ALL SELECT 'Chicken', 'Layer pullet (growing, brown-egg breed)', 'Complete starter, grower or developer feed', 'Commercial layer feed', 100.00, NULL, NULL, 'Use the feed made for the age of the birds (starter, grower, developer).', 1
    UNION ALL SELECT 'Rabbit', 'Adult rabbit', 'Pellets, hay and greens', 'Rabbit pellets (grass-based, 15-20% fiber)', 100.00, NULL, NULL, 'Amount of pellets per day.', 1
    UNION ALL SELECT 'Rabbit', 'Adult rabbit', 'Pellets, hay and greens', 'Grass hay', NULL, NULL, NULL, 'Unlimited, about 85 to 90% of the diet.', 2
    UNION ALL SELECT 'Rabbit', 'Adult rabbit', 'Pellets, hay and greens', 'Fresh leafy vegetables', NULL, NULL, NULL, 'About 1 cup per 2 lb (1 kg) of body weight per day.', 3
) x
JOIN animals a ON a.common_name = x.animal
JOIN feeding_plans p ON p.animal_id = a.id AND p.life_stage = x.stage
JOIN feeds f ON f.feed_name = x.feed;

-- Sources of each plan
INSERT INTO feeding_plan_sources (plan_id, source_id)
SELECT p.id, s.id
FROM feeding_plans p
JOIN animals a ON a.id = p.animal_id
JOIN (
    SELECT 'Dog' AS animal, 'MSD Veterinary Manual - Daily maintenance energy requirements for dogs and cats' AS source
    UNION ALL SELECT 'Dog', 'WSAVA Global Nutrition Guidelines'
    UNION ALL SELECT 'Dog', 'Royal Canin Academy - Calculating the energy content of pet food'
    UNION ALL SELECT 'Dog', 'USDA FoodData Central'
    UNION ALL SELECT 'Cat', 'MSD Veterinary Manual - Daily maintenance energy requirements for dogs and cats'
    UNION ALL SELECT 'Cat', 'WSAVA Global Nutrition Guidelines'
    UNION ALL SELECT 'Cat', 'Royal Canin Academy - Calculating the energy content of pet food'
    UNION ALL SELECT 'Cattle', 'A Nutritional Investigation of Major Feed Types and Feed Rations Used in Medium-Scale Dairy Production Systems in Sri Lanka (Animals 2022, 12(18):2391)'
    UNION ALL SELECT 'Cattle', 'USDA AMS National Organic Program - Dry matter demand and intake'
    UNION ALL SELECT 'Cattle', 'Alberta Agriculture - How much feed will my cow eat?'
    UNION ALL SELECT 'Goat', 'Merck Veterinary Manual - Nutrition of goats'
    UNION ALL SELECT 'Goat', 'A Nutritional Investigation of Major Feed Types and Feed Rations Used in Medium-Scale Dairy Production Systems in Sri Lanka (Animals 2022, 12(18):2391)'
    UNION ALL SELECT 'Sheep', 'Merck Veterinary Manual - Nutrition of sheep'
    UNION ALL SELECT 'Sheep', 'A Nutritional Investigation of Major Feed Types and Feed Rations Used in Medium-Scale Dairy Production Systems in Sri Lanka (Animals 2022, 12(18):2391)'
    UNION ALL SELECT 'Horse', 'University of Minnesota Extension - Hay in horse diets'
    UNION ALL SELECT 'Horse', 'MSD Veterinary Manual - Nutritional requirements of horses'
    UNION ALL SELECT 'Horse', 'USDA AMS National Organic Program - Dry matter demand and intake'
    UNION ALL SELECT 'Horse', 'A Nutritional Investigation of Major Feed Types and Feed Rations Used in Medium-Scale Dairy Production Systems in Sri Lanka (Animals 2022, 12(18):2391)'
    UNION ALL SELECT 'Domestic Pig', 'AHDB - Feeding growing and finishing pigs'
    UNION ALL SELECT 'Domestic Pig', 'Merck Veterinary Manual - Feeding levels and practices in pigs'
    UNION ALL SELECT 'Chicken', 'PoultryHub - Nutrient requirements of egg laying chickens'
    UNION ALL SELECT 'Rabbit', 'Merck Veterinary Manual - Diet for rabbits (pet owner version)'
    UNION ALL SELECT 'Rabbit', 'Scottish Government - Pet rabbit welfare guidance'
) m ON m.animal = a.common_name
JOIN sources s ON s.title = m.source;

-- ---------------------------------------------------------------------------
-- Foods to avoid
-- ---------------------------------------------------------------------------
INSERT INTO animal_unsafe_foods (animal_id, food_name, severity, reason, source_id)
SELECT a.id, x.food, x.severity, x.reason, s.id
FROM (
    SELECT 'Dog' AS animal, 'Chocolate' AS food, 'toxic' AS severity, 'Contains caffeine and theobromine, which are toxic to dogs. Dark and baking chocolate are the most dangerous.' AS reason, 'ASPCA - People foods to avoid feeding your pets' AS source
    UNION ALL SELECT 'Dog', 'Grapes and raisins', 'toxic', 'Can cause kidney failure in dogs; the toxic substance is not fully known, so avoid them completely.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Dog', 'Onions, garlic and chives', 'toxic', 'Can damage red blood cells and cause anemia. This includes cooked dishes and curries that contain them.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Dog', 'Xylitol (artificial sweetener)', 'toxic', 'Found in sugar-free gum, sweets, baked goods and some toothpaste; can cause a dangerous drop in blood sugar and liver damage in dogs.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Dog', 'Macadamia nuts', 'toxic', 'Can cause weakness, vomiting and tremors in dogs.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Dog', 'Alcohol', 'toxic', 'Even small amounts are dangerous to pets.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Dog', 'Raw yeast dough', 'toxic', 'Can rise in the stomach, causing gas, bloating and alcohol poisoning.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Dog', 'Tea, coffee and other caffeine', 'toxic', 'Caffeine is toxic to pets.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Dog', 'Milk and dairy', 'limit', 'Many dogs lack the enzyme needed to digest lactose and get diarrhea.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Cat', 'Chocolate', 'toxic', 'Contains caffeine and theobromine, which are toxic to cats.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Cat', 'Grapes and raisins', 'toxic', 'Avoid completely until more is known about the toxic substance.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Cat', 'Onions, garlic and chives', 'toxic', 'Can damage red blood cells and cause anemia. Avoid in any dish.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Cat', 'Alcohol', 'toxic', 'Even small amounts are dangerous to pets.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Cat', 'Raw yeast dough', 'toxic', 'Can rise in the stomach, causing gas, bloating and alcohol poisoning.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Cat', 'Tea, coffee and other caffeine', 'toxic', 'Caffeine is toxic to pets.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Cat', 'Milk and dairy', 'limit', 'Many adult cats cannot digest lactose well and get diarrhea.', 'ASPCA - People foods to avoid feeding your pets'
    UNION ALL SELECT 'Cattle', 'Sudden large increases in concentrate or grain', 'limit', 'Can cause rumen acidosis. Raise concentrate slowly over days to weeks. (Described for sheep and goats; the same mechanism applies to cattle.)', 'Merck Veterinary Manual - Nutrition of sheep'
    UNION ALL SELECT 'Cattle', 'Moldy or spoiled forage and grain', 'avoid', 'Forage and feed should be clean and free of mold.', 'Merck Veterinary Manual - Nutrition of sheep'
    UNION ALL SELECT 'Goat', 'Sudden increases in grain or concentrate', 'limit', 'Can cause lactic acidosis, with bloating, diarrhea and collapse. The risk depends on the type and amount of concentrate.', 'Merck Veterinary Manual - Nutrition of goats'
    UNION ALL SELECT 'Goat', 'High-grain, high-phosphorus diets for male goats', 'limit', 'Bucks, buck kids and wethers are at risk of urinary stones, which can be fatal. Keep magnesium low and the calcium to phosphorus ratio near 2 to 1; ask a veterinarian.', 'Merck Veterinary Manual - Nutrition of goats'
    UNION ALL SELECT 'Goat', 'Moldy or spoiled forage', 'avoid', 'Forage should be clean and free of mold.', 'Merck Veterinary Manual - Nutrition of goats'
    UNION ALL SELECT 'Sheep', 'Sudden increases in grain or concentrate', 'limit', 'Can cause lactic acidosis, with bloating, diarrhea and collapse.', 'Merck Veterinary Manual - Nutrition of sheep'
    UNION ALL SELECT 'Sheep', 'High-grain, high-phosphorus diets for rams and wethers', 'limit', 'Rams, ram lambs and wethers are at risk of urinary stones, which can be fatal. Ask a veterinarian.', 'Merck Veterinary Manual - Nutrition of sheep'
    UNION ALL SELECT 'Sheep', 'Moldy or spoiled forage', 'avoid', 'Forage should be clean and free of mold.', 'Merck Veterinary Manual - Nutrition of sheep'
    UNION ALL SELECT 'Domestic Pig', 'Uncooked food waste (swill) and meat products', 'avoid', 'A route for African swine fever virus to reach pigs. Do not feed uncooked kitchen waste or meat products.', 'WOAH - African swine fever'
    UNION ALL SELECT 'Domestic Pig', 'Moldy grain and feed', 'avoid', 'Molds and mycotoxins reduce growth and cause reproductive problems. Grains should be as free as possible from mycotoxins.', 'Merck Veterinary Manual - Feeding levels and practices in pigs'
    UNION ALL SELECT 'Rabbit', 'Alfalfa hay and alfalfa pellets (adult rabbits)', 'limit', 'Too much calcium over time can damage the kidneys and cause bladder and kidney stones. Fine for young, growing rabbits.', 'Merck Veterinary Manual - Diet for rabbits (pet owner version)'
    UNION ALL SELECT 'Rabbit', 'High-salt, high-fat or caffeine-containing treats', 'avoid', 'Some plants and foods are unsafe for rabbits; check with a veterinarian before introducing new foods.', 'Merck Veterinary Manual - Diet for rabbits (pet owner version)'
    UNION ALL SELECT 'Rabbit', 'Muesli-style mixes, dried fruit and nuts', 'limit', 'Rabbits pick out dried fruit and nuts and ignore their pellets, so they miss the nutrients they need.', 'Merck Veterinary Manual - Diet for rabbits (pet owner version)'
) x
JOIN animals a ON a.common_name = x.animal
LEFT JOIN sources s ON s.title = x.source;
