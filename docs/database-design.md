# GymLog

## 1. Cilj i opseg

GymLog je aplikacija za vođenje dnevnika treninga. Prva verzija radi lokalno za jednog korisnika.

Model treba omogućiti:

- stvaranje treninga s datumom, nazivom i neobveznom bilješkom;
- dodavanje vježbi u trening;
- zapisivanje svake serije s brojem ponavljanja i težinom u kilogramima;
- pregled povijesti i detalja treninga;
- uređivanje i brisanje podataka uz potvrdu.

Koristimo četiri tablice: `workouts`, `exercises`, `workout_exercises` i `sets`.

Prijava korisnika, grafovi, osobni rekordi i predlošci treninga nisu dio ovog koraka.

## 2. Osnovni pojmovi i veze

**Redak** predstavlja jedan zapis u tablici, primjerice jedan trening.

**Primarni ključ (PK)** jednoznačno označava redak. U svakoj tablici to je polje `id`. Vrijednost mora biti jedinstvena
unutar te tablice.

**Strani ključ (FK)** povezuje redak s postojećim retkom druge tablice. Primjerice, `workout_id` pokazuje kojem treningu
zapis pripada.

Jednak broj ID-a u različitim tablicama ne znači da je riječ o istom podatku. Trening s ID-em 1 i vježba s ID-em 1
zasebni su zapisi.

Veze između tablica:

- Jedan trening ima više zapisa u `workout_exercises`.
- Jedna kataloška vježba može se pojaviti u više zapisa u `workout_exercises`.
- Jedan zapis u `workout_exercises` ima više serija u `sets`.

Trening može sadržavati više vježbi, a ista vježba može se koristiti u više treninga. Tablica `workout_exercises`
povezuje te dvije strane.

## 3. Predložene tablice i polja

U nastavku je opisan model tablica i značenje njihovih polja. Točna SQL provedba nalazi se u datoteci `schema.sql`, a
stanje izrađenih tablica i rezultati provjera navedeni su u poglavlju 7.

### `workouts` — treninzi

Jedan redak predstavlja jedan trening.

| Polje          | Značenje                                           | Vrsta podatka                               | Obvezno |
|----------------|----------------------------------------------------|---------------------------------------------|---------|
| `id`           | Jedinstveni identifikator treninga; primarni ključ | Automatski dodijeljen pozitivan cijeli broj | Da      |
| `workout_date` | Datum održavanja treninga                          | Datum bez vremena                           | Da      |
| `name`         | Naziv treninga, primjerice Upper A                 | Kratki tekst                                | Da      |
| `note`         | Dodatna bilješka o treningu                        | Tekst                                       | Ne      |

Datum i naziv nisu identifikatori: dva treninga mogu imati isti naziv ili biti održana istog dana.

### `exercises` — katalog vježbi

Jedan redak predstavlja jednu vježbu koju možemo koristiti u različitim treninzima.

| Polje  | Značenje                                         | Vrsta podatka                               | Obvezno |
|--------|--------------------------------------------------|---------------------------------------------|---------|
| `id`   | Jedinstveni identifikator vježbe; primarni ključ | Automatski dodijeljen pozitivan cijeli broj | Da      |
| `name` | Naziv vježbe, primjerice Bench press             | Kratki tekst                                | Da      |

Ovdje ne spremamo težinu i ponavljanja jer se oni razlikuju između treninga i pojedinačnih serija.

### `workout_exercises` — vježbe u konkretnom treningu

Jedan redak predstavlja dodavanje jedne kataloške vježbe u određeni trening.

| Polje         | Značenje                                                           | Vrsta podatka                               | Obvezno |
|---------------|--------------------------------------------------------------------|---------------------------------------------|---------|
| `id`          | Jedinstveni identifikator tog pojavljivanja vježbe; primarni ključ | Automatski dodijeljen pozitivan cijeli broj | Da      |
| `workout_id`  | Trening kojem zapis pripada; strani ključ prema `workouts.id`      | Pozitivan cijeli broj                       | Da      |
| `exercise_id` | Kataloška vježba; strani ključ prema `exercises.id`                | Pozitivan cijeli broj                       | Da      |
| `position`    | Redoslijed vježbe unutar treninga                                  | Pozitivan cijeli broj                       | Da      |

Bench press u treningu Upper A i Bench press u treningu Upper B imaju isti `exercise_id`, ali različite ID-eve u ovoj
tablici.

Tako razlikujemo izvođenje iste vježbe u različitim treninzima.

### `sets` — pojedinačne serije

Jedan redak predstavlja jednu seriju vježbe u konkretnom treningu.

| Polje                 | Značenje                                                                             | Vrsta podatka                               | Obvezno |
|-----------------------|--------------------------------------------------------------------------------------|---------------------------------------------|---------|
| `id`                  | Jedinstveni identifikator serije; primarni ključ                                     | Automatski dodijeljen pozitivan cijeli broj | Da      |
| `workout_exercise_id` | Pojavljivanje vježbe kojem serija pripada; strani ključ prema `workout_exercises.id` | Pozitivan cijeli broj                       | Da      |
| `set_number`          | Redni broj serije unutar tog pojavljivanja vježbe                                    | Pozitivan cijeli broj                       | Da      |
| `reps`                | Broj izvedenih ponavljanja                                                           | Pozitivan cijeli broj                       | Da      |
| `weight_kg`           | Težina korištena u seriji, izražena u kilogramima                                    | Točan decimalni broj                        | Da      |

Serija se povezuje s `workout_exercises`, a ne samo s katalogom `exercises`. Tako znamo kojoj vježbi i kojem treningu
pripada.

Ne trebamo dodatni `workout_id` u tablici `sets`: trening već možemo pronaći preko povezanog zapisa u
`workout_exercises`.

## 4. Provjera modela na primjeru

Primjer sadrži dva treninga:

- **Upper A, 2026-09-09:** Bench press — 8 ponavljanja sa 60 kg i 6 ponavljanja sa 65 kg; Lat pulldown — 10 ponavljanja
  sa 40 kg.
- **Upper B, 2026-09-11:** Bench press — 8 ponavljanja sa 62.5 kg.

ID-evi u nastavku služe samo kao primjer. `NULL` označava da bilješka nije unesena; nije tekst koji korisnik upisuje.

### Zapisi u `workouts`

| id | workout_date | name    | note |
|----|--------------|---------|------|
| 1  | 2026-09-09   | Upper A | NULL |
| 2  | 2026-09-11   | Upper B | NULL |

### Zapisi u `exercises`

| id | name         |
|----|--------------|
| 1  | Bench press  |
| 2  | Lat pulldown |

Bench press postoji samo jednom u katalogu, iako se koristi u oba treninga.

### Zapisi u `workout_exercises`

| id | workout_id | exercise_id | position |
|----|------------|-------------|----------|
| 1  | 1          | 1           | 1        |
| 2  | 1          | 2           | 2        |
| 3  | 2          | 1           | 1        |

Prvi redak znači: Bench press je prva vježba u treningu Upper A.

Treći redak znači: Bench press je prva vježba u treningu Upper B.

### Zapisi u `sets`

| id | workout_exercise_id | set_number | reps | weight_kg |
|----|---------------------|------------|------|-----------|
| 1  | 1                   | 1          | 8    | 60.00     |
| 2  | 1                   | 2          | 6    | 65.00     |
| 3  | 2                   | 1          | 10   | 40.00     |
| 4  | 3                   | 1          | 8    | 62.50     |

Serija s ID-em 4 pripada zapisu `workout_exercises` s ID-em 3. Taj zapis povezuje Bench press s treningom Upper B.

Očekivani broj redaka:

| Tablica             | Broj redaka |
|---------------------|-------------|
| `workouts`          | 2           |
| `exercises`         | 2           |
| `workout_exercises` | 3           |
| `sets`              | 4           |

## 5. Pravila prve verzije

Ova pravila vrijede za prvu verziju aplikacije. Njihovu provedbu u bazi i PHP-u dodajemo postupno, uz provjere svake
izrađene cjeline.

### Obvezni podatci i unos

- Trening mora imati valjan datum i naziv koji nije prazan niti se sastoji samo od razmaka.
- Bilješka treninga nije obvezna.
- Vježba mora imati naziv koji nije prazan niti se sastoji samo od razmaka.
- Novi trening može privremeno biti bez vježbi, a dodana vježba bez serija. Podatke unosimo postupno.
- Broj ponavljanja mora biti cijeli broj veći od nule.
- Težina može biti nula ili pozitivan broj s najviše dvije decimale.
- Težina nula označava izvođenje bez dodatnog opterećenja. Tjelesnu težinu ne pribrajamo automatski.
- Negativne težine i decimalna ponavljanja nisu podržani u prvoj verziji.

### Ponavljanje vježbi i redoslijed

- Istu katalošku vježbu možemo koristiti u više treninga.
- U jednom treningu istu katalošku vježbu dodajemo jednom, a zatim joj dodajemo više serija.
- `position` određuje redoslijed vježbi unutar treninga.
- `set_number` određuje redoslijed serija unutar jednog zapisa `workout_exercises`.
- Dvije vježbe istog treninga ne smiju imati jednaku poziciju.
- Dvije serije istog pojavljivanja vježbe ne smiju imati jednak redni broj.
- Brojevi mogu imati praznine nakon brisanja; prikazujemo ih uzlaznim redoslijedom.
- ID-eve ne koristimo kao zamjenu za redoslijed.

### Brisanje i povezani podatci

- Brisanje treninga briše njegova pojavljivanja vježbi i pripadajuće serije.
- Brisanje treninga ne briše vježbe iz kataloga.
- Uklanjanje vježbe iz jednog treninga briše serije tog pojavljivanja, ali ne utječe na druge treninge.
- Brisanje jedne serije ne briše vježbu ni trening.
- Katalošku vježbu koja se koristi u treningu nije dopušteno obrisati.
- Katalošku vježbu koja se nigdje ne koristi moguće je obrisati.
- Prije brisanja sučelje traži potvrdu i objašnjava što će biti obrisano.

Promjena naziva kataloške vježbe prikazat će novi naziv i u ranijim treninzima jer svi koriste isti kataloški zapis.
Ovaj model ne sprema povijest promjena naziva.

## 6. Dogovorene odluke

- **Ista vježba u treningu:** istu katalošku vježbu dodajemo jednom u pojedini trening, a zatim joj dodajemo više
  serija. Možemo je koristiti u neograničenom broju različitih treninga.
- **Ponavljanja:** broj ponavljanja mora biti cijeli broj veći od nule.
- **Težina:** dopuštena je nula ili pozitivna vrijednost s najviše dvije decimale. Nula označava izvođenje bez dodatnog
  opterećenja.
- **Datum treninga:** dopušten je današnji ili prošli datum. Budući datumi nisu dopušteni jer prva verzija služi
  bilježenju odrađenih treninga.
- **Nazivi vježbi:** nazivi u katalogu moraju biti jedinstveni bez razlikovanja velikih i malih slova. Primjerice,
  `Bench press` i `bench press` smatraju se istim nazivom.
- **Brisanje treninga:** brišu se njegova pojavljivanja vježbi i pripadajuće serije. Kataloške vježbe ostaju sačuvane.
- **Uklanjanje vježbe iz treninga:** brišu se serije tog pojavljivanja. Drugi treninzi i katalog ostaju sačuvani.
- **Brisanje serije:** briše se samo odabrana serija.
- **Brisanje kataloške vježbe:** dopušteno je samo ako se vježba ne koristi ni u jednom treningu.
- **Potvrda brisanja:** sučelje prije brisanja traži potvrdu i objašnjava koji će podatci biti obrisani.
- **Preimenovanje vježbe:** promjena naziva kataloške vježbe prikazuje se i u ranijim treninzima. Povijest promjena
  naziva ne spremamo.

Za tablicu `sets` još treba odrediti točne SQL tipove, dopuštene raspone brojeva i težine te ograničenja baze.

## 7. Trenutačno stanje i sljedeći korak

U bazi `gymlog` na MySQL poslužitelju 8.4.11 izrađene su tablice `workouts`, `exercises` i `workout_exercises`.

Sve tri koriste InnoDB, skup znakova `utf8mb4` i kolaciju `utf8mb4_0900_as_ci`. Svaka ima primarni ključ `id` tipa `INT UNSIGNED` s automatskim dodjeljivanjem vrijednosti.

### Stupci izrađenih tablica

| Tablica | Stupac | Tip i obveznost |
| --- | --- | --- |
| `workouts` | `workout_date` | `DATE NOT NULL` |
| `workouts` | `name` | `VARCHAR(100) NOT NULL` |
| `workouts` | `note` | `TEXT`, dopušten `NULL` |
| `exercises` | `name` | `VARCHAR(100) NOT NULL` |
| `workout_exercises` | `workout_id` | `INT UNSIGNED NOT NULL` |
| `workout_exercises` | `exercise_id` | `INT UNSIGNED NOT NULL` |
| `workout_exercises` | `position` | `INT UNSIGNED NOT NULL` |

### Provedena ograničenja

- Nazivi treninga i vježbi ne smiju biti `NULL`, prazni ili sastavljeni samo od običnih razmaka.
- Naziv kataloške vježbe mora biti jedinstven bez razlikovanja velikih i malih slova.
- Pozicija vježbe u treningu mora biti veća od nule.
- Par `workout_id` i `exercise_id` mora biti jedinstven.
- Par `workout_id` i `position` mora biti jedinstven.
- Strani ključevi zahtijevaju postojanje povezanog treninga i kataloške vježbe.
- Brisanje treninga preko `ON DELETE CASCADE` uklanja njegove zapise u `workout_exercises`.
- `ON DELETE RESTRICT` sprječava brisanje kataloške vježbe koja se koristi u treningu.

### Ručne provjere

Potvrđeni su unos i čitanje valjanih podataka te korištenje iste kataloške vježbe u različitim treninzima.

Provjereno je odbijanje nevaljanih naziva, duplikata naziva koji se razlikuju samo po veličini slova, ponovljene vježbe u istom treningu, zauzete pozicije, pozicije nula i nepostojećih povezanih ID-eva.

Na zasebnim probnim zapisima potvrđeno je da brisanje korištene kataloške vježbe bude odbijeno, dok brisanje treninga uklanja njegovu vezu i čuva katalošku vježbu. Nekorištenu katalošku vježbu moguće je obrisati.

Probni podatci nisu dio početne SQL sheme.

### Sljedeća cjelina

Slijedi tablica `sets`, koja će spremati pojedinačne serije vježbe u konkretnom treningu: redni broj serije, ponavljanja i težinu.

Povezivanje aplikacije preko PDO-a i PHP validacija, uključujući zabranu budućeg datuma treninga, još nisu izrađeni.
