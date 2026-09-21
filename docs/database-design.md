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

SQL tipovi i ograničenja svih četiriju tablica definirani su u `database/schema.sql`. Trenutačno stanje i opseg provjera
opisani su u poglavlju 7. Provjera korisničkog unosa u PHP-u slijedi pri izradi aplikacijskih funkcionalnosti.

## 7. Trenutačno stanje i sljedeći korak

U bazi `gymlog` na MySQL poslužitelju 8.4.11 izrađene su tablice `workouts`, `exercises`, `workout_exercises` i `sets`.

Sve četiri koriste InnoDB, skup znakova `utf8mb4` i kolaciju `utf8mb4_0900_as_ci`. Svaka ima primarni ključ `id` tipa
`INT UNSIGNED` s automatskim dodjeljivanjem vrijednosti.

### Stupci izrađenih tablica

| Tablica             | Stupac                | Tip i obveznost              |
|---------------------|-----------------------|------------------------------|
| `workouts`          | `workout_date`        | `DATE NOT NULL`              |
| `workouts`          | `name`                | `VARCHAR(100) NOT NULL`      |
| `workouts`          | `note`                | `TEXT`, dopušten `NULL`      |
| `exercises`         | `name`                | `VARCHAR(100) NOT NULL`      |
| `workout_exercises` | `workout_id`          | `INT UNSIGNED NOT NULL`      |
| `workout_exercises` | `exercise_id`         | `INT UNSIGNED NOT NULL`      |
| `workout_exercises` | `position`            | `INT UNSIGNED NOT NULL`      |
| `sets`              | `workout_exercise_id` | `INT UNSIGNED NOT NULL`      |
| `sets`              | `set_number`          | `SMALLINT UNSIGNED NOT NULL` |
| `sets`              | `reps`                | `SMALLINT UNSIGNED NOT NULL` |
| `sets`              | `weight_kg`           | `DECIMAL(6,2) NOT NULL`      |

Uz definirana ograničenja, redni broj serije i ponavljanja imaju raspon od 1 do 65535. Težina se pohranjuje s dvije
decimale, u rasponu od 0.00 do 9999.99 kg. Nula označava izvođenje bez dodatnog opterećenja.

### Definirana ograničenja

- Nazivi treninga i vježbi ne smiju biti `NULL`, prazni ili sastavljeni samo od običnih razmaka.
- Naziv kataloške vježbe mora biti jedinstven bez razlikovanja velikih i malih slova.
- Pozicija vježbe, redni broj serije i broj ponavljanja moraju biti veći od nule.
- Težina serije mora biti najmanje nula.
- Par `workout_id` i `exercise_id` mora biti jedinstven.
- Par `workout_id` i `position` mora biti jedinstven.
- Par `workout_exercise_id` i `set_number` mora biti jedinstven.
- Strani ključevi zahtijevaju postojanje povezanih roditeljskih zapisa.
- Brisanje treninga preko `ON DELETE CASCADE` uklanja njegova pojavljivanja vježbi i njihove serije.
- Brisanje pojavljivanja vježbe preko `ON DELETE CASCADE` uklanja njegove serije.
- `ON DELETE RESTRICT` sprječava brisanje kataloške vježbe koja se koristi u treningu.

### Provedene provjere

Za prve tri tablice ranije su potvrđeni valjani unosi, korištenje iste kataloške vježbe u različitim treninzima te
odbijanje nevaljanih naziva, duplikata, pozicije nula i nepostojećih povezanih ID-eva.

Na probnim zapisima tih tablica potvrđeno je da brisanje korištene kataloške vježbe bude odbijeno, dok brisanje treninga
uklanja njegovu vezu i čuva katalošku vježbu. Nekorištenu katalošku vježbu moguće je obrisati.

Tablica `sets` uspješno je stvorena. Nevaljani unosi korišteni u ručnoj provjeri bili su odbijeni.

### Povezivanje aplikacije s bazom

Aplikacija koristi zaseban MySQL račun `gymlog_app` s pravima `SELECT`, `INSERT`, `UPDATE` i `DELETE` nad bazom
`gymlog`.

Konfiguracijski predložak nalazi se u `config/database.example.php`. Stvarni pristupni podatci nalaze se u
`config/database.local.php`, koja je izuzeta iz Gita. Datoteka `src/database.php` učitava konfiguraciju i vraća PDO
objekt.

Početna stranica dohvaća treninge sortirane po datumu silazno, a zatim po ID-u silazno. Razlikuje pogrešku dohvaćanja,
prazan popis i tablicu treninga. Vrijednosti se prije ispisa u HTML obrađuju funkcijom `htmlspecialchars()`.

### Stvaranje treninga

Obrazac prima datum, naziv i neobveznu bilješku. Obrada POST zahtjeva uključuje provjeru CSRF tokena i validaciju na
poslužitelju:

- naziv nakon uklanjanja rubnih razmaka mora imati od 1 do 100 znakova;
- bilješka nakon uklanjanja rubnih razmaka smije imati najviše 5000 znakova;
- datum mora biti stvaran kalendarski datum u obliku `YYYY-MM-DD`;
- dopušteni raspon datuma je od `1000-01-01` do današnjeg dana u vremenskoj zoni `Europe/Zagreb`.

Valjani podatci spremaju se pripremljenim PDO upitom. Prazna bilješka sprema se kao SQL `NULL`. Nakon uspjeha aplikacija
preusmjerava na popis uz HTTP status 303.

Pri pogrešci baze aplikacija postavlja HTTP 500, prikazuje generičku poruku i zadržava unesene vrijednosti. Tehnički
detalj zapisuje se u PHP zapisnik.

### Provedene provjere aplikacije

Ručno su potvrđeni:

- prikaz praznog popisa;
- spremanje treninga i prikaz na popisu;
- osvježavanje popisa bez dodatnog unosa;
- pohrana i prikaz posebnih znakova u nazivu;
- spremanje prazne bilješke kao SQL `NULL`;
- odbijanje naziva sastavljenog od razmaka uz očuvanu bilješku;
- odbijanje izmijenjenog CSRF tokena;
- odbijanje budućeg, nepostojećeg i prerano datiranog treninga;
- generička poruka pri neuspjelom dohvaćanju ili spremanju zbog neispravne konfiguracije baze.

### Preostale provjere

Preostaju provjere sortiranja više treninga, uključujući treninge istog datuma, te dodatne provjere graničnih duljina
polja.

Za tablicu `sets` ostaju odgođeni valjani unosi, provjera jedinstvenosti redoslijeda uz valjane roditeljske zapise te
izolirano i kaskadno brisanje serija.

Validacija ponavljanja i težine slijedi uz njihove obrasce. Sama pretvorba vrijednosti u SQL tip ne zamjenjuje provjeru
izvornog unosa.

### Detalji treninga

Stranica detalja dohvaća pojedinačni trening prema ID-u pomoću PDO pripremljenog upita. Prikazuje datum, naziv i
bilješku. Za praznu bilješku prikazuje se poruka, a tekstualni izlaz HTML-escapira se uz očuvanje prijeloma redaka
bilješke.

Korisnik je potvrdio:

- prikaz postojećeg treninga uz HTTP 200;
- odbijanje nedostajućeg ili nevaljanog ID-a uz HTTP 400;
- poruku za nepostojeći trening uz HTTP 404;
- prikaz posebnih znakova kao običnog teksta.

Obrada pogreške baze implementirana je uz HTTP 500, generičku poruku i zapis tehničkih pojedinosti u PHP log. Ta
provjera na stranici detalja još nije zasebno potvrđena.

### Uređivanje treninga

Obrazac se popunjava postojećim datumom, nazivom i bilješkom. Prije obrade unosa provjeravaju se ID i postojanje
treninga. POST obrada uključuje CSRF zaštitu i ista pravila validacije kao stvaranje treninga.

Promjene se spremaju pripremljenim PDO UPDATE upitom ograničenim na ID treninga. Prazna bilješka sprema se kao SQL
`NULL`. Nakon uspjeha slijedi preusmjeravanje statusom 303 na detalje istog treninga.

Potvrđeni su spremanje izmjena, spremanje bez promjena, pražnjenje bilješke, očuvanje unosa nakon nevaljanog naziva,
odbijanje izmijenjenog CSRF tokena i obrada nevaljanog ili nepostojećeg ID-a. Zasebno je potvrđeno da POST za
nepostojeći trening vraća 404 bez spremanja.

Obrada pogreške spremanja implementirana je uz HTTP 500, generičku poruku i očuvanje obrasca. Izolirana provjera
pogreške UPDATE upita još nije potvrđena.

### Zajednička validacija i obrazac

Stvaranje i uređivanje treninga koriste funkciju `validateWorkout()` iz `src/workout-validation.php`. Funkcija prima
ulazno polje i vraća obrađeni datum, naziv, bilješku i pogreške validacije.

Zajednički HTML nalazi se u `templates/workout-form.php`. Stranice pripremaju vrijednosti polja, pogreške, CSRF token,
odredište obrasca i tekst gumba. Dohvat podataka, CSRF provjera, spremanje i preusmjeravanje ostaju u pojedinačnim
stranicama.

Nakon izdvajanja validacije potvrđene su provjere valjanog unosa, naziva od razmaka uz očuvanu bilješku, budućeg i
nepostojećeg datuma te prazne bilješke na oba obrasca.

Nakon povezivanja zajedničkog predloška potvrđeno je uređivanje istog treninga bez novog retka, očuvanje bilješke nakon
nevaljanog naziva te HTTP 404 bez obrasca za nepostojeći trening.

### Brisanje treninga

GET zahtjev prikazuje potvrdu s nazivom, datumom i upozorenjem o posljedicama brisanja. Brisanje se izvršava samo POST
zahtjevom, nakon provjere ID-a, postojanja treninga i CSRF tokena.

Pripremljeni PDO DELETE upit ograničen je na odabrani ID. Nakon uspjeha slijedi preusmjeravanje na popis uz HTTP 303.
Pogreška brisanja obrađuje se statusom 500 i generičkom porukom, uz zapis tehničkih detalja u PHP log.

Potvrđeno je da otvaranje, osvježavanje i odustajanje ne brišu trening, izmijenjeni CSRF token vraća 403 bez brisanja,
valjan POST uklanja odabrani probni trening, a njegovi detalji nakon brisanja vraćaju 404.

Kaskadno brisanje povezanih zapisa vježbi i serija definirano je stranim ključevima, ali još nije provjereno na treningu
s tim zapisima. Izolirana provjera pogreške DELETE upita također ostaje otvorena.

### Sljedeća cjelina

Slijedi katalog vježbi: pregled postojećih vježbi i stvaranje nove vježbe uz validaciju naziva.