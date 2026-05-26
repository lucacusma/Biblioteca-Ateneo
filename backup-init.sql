DROP DATABASE IF EXISTS biblioteca_ateneo;
CREATE DATABASE biblioteca_ateneo;
USE biblioteca_ateneo;

/* AUTORE */
CREATE TABLE Autore (
    ID_Autore INT AUTO_INCREMENT PRIMARY KEY,
    Nome varchar(100) NOT NULL,
    Cognome varchar(100) NOT NULL,
    Data_Nascita DATE,
    Data_Morte DATE
);

/* OPERA */
CREATE TABLE Opera(
    ID_Opera INT AUTO_INCREMENT PRIMARY KEY,
    Titolo VARCHAR(100) NOT NULL,
    Anno_Pubblicazione INT,
    Lingua_Originale varchar(50)
);

/* SCRITTURA */
CREATE TABLE Scrittura(
    Autore INT,
    Opera INT,
    PRIMARY KEY(Autore, Opera),
    FOREIGN KEY (Autore) REFERENCES Autore(ID_Autore) ON DELETE CASCADE,
    FOREIGN KEY (Opera) REFERENCES Opera(ID_Opera) ON DELETE CASCADE
);

/* CATEGORIA */
CREATE TABLE Categoria (
    ID_Categoria INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL,
    Macro_Categoria INT
);

ALTER TABLE Categoria ADD FOREIGN KEY (Macro_Categoria) REFERENCES Categoria(ID_Categoria);

/* CLASSIFICAZIONE */
CREATE TABLE Classificazione(
    Opera INT,
    Categoria INT,
    PRIMARY KEY(Opera, Categoria),
    FOREIGN KEY (Opera) REFERENCES Opera(ID_Opera),
    FOREIGN KEY (Categoria) REFERENCES Categoria(ID_Categoria)
);

/* EDITORE */
CREATE TABLE Editore(
    ID_Editore INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL,
    Sede_Legale VARCHAR(200),
    Sito_Web VARCHAR(255)
);

/* EDIZIONE */
CREATE TABLE Edizione(
    ISBN VARCHAR(20) PRIMARY KEY,
    Opera INT NOT NULL,
    Editore INT NOT NULL,
    Lingua_Pubblicazione VARCHAR(50),
    Anno_Edizione INT,
    Pagine INT,
    FOREIGN KEY (Opera) REFERENCES Opera(ID_Opera),
    FOREIGN KEY (Editore) REFERENCES Editore(ID_Editore)
);

CREATE TABLE Sede(
    ID_Sede INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL,
    Indirizzo VARCHAR(255),
    Telefono VARCHAR(20)
);

/* COPIA */
CREATE TABLE Copia(
    ID_Copia INT AUTO_INCREMENT PRIMARY KEY,
    Stato ENUM('Disponibile', 'In prestito', 'Restauro', 'Smarrito', 'Dismesso') DEFAULT 'Disponibile',
    Data_Acquisizione DATE DEFAULT (CURRENT_DATE),
    ISBN VARCHAR(20) NOT NULL,
    Sede INT NOT NULL,
    FOREIGN KEY (ISBN) REFERENCES Edizione(ISBN),
    FOREIGN KEY (Sede) REFERENCES Sede(ID_Sede)
);

/* DIPARTIMENTO */
CREATE TABLE Dipartimento(
    ID_Dipartimento INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL,
    Direttore VARCHAR(100)
);

/* UTENTE */
CREATE TABLE Utente(
    ID_Utente INT AUTO_INCREMENT PRIMARY KEY,
    Codice_Tessera VARCHAR(20) NOT NULL UNIQUE,
    Nome VARCHAR(100) NOT NULL,
    Cognome VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Telefono VARCHAR(20),
    Stato ENUM('Attivo', 'Sospeso') DEFAULT 'Attivo',
    Dipartimento INT NOT NULL,
    Tipo_Utente ENUM('Studente', 'Professore', 'Personale') NOT NULL,
    Matricola VARCHAR(20),
    Corso_Laurea VARCHAR(100),
    Ufficio VARCHAR(50),
    FOREIGN KEY (Dipartimento) REFERENCES Dipartimento(ID_Dipartimento)
);

/* BIBLIOTECARIO */
CREATE TABLE Bibliotecario(
    ID_Bibliotecario INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(100) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Nome VARCHAR(100) NOT NULL,
    Cognome VARCHAR(100),
    Stato ENUM('Attivo', 'Disattivato') DEFAULT 'Attivo'
);

/* PRENOTAZIONE */
CREATE TABLE Prenotazione(
    ID_Prenotazione INT AUTO_INCREMENT PRIMARY KEY,
    Utente INT NOT NULL,
    ISBN VARCHAR(20) NOT NULL,
    Data DATE DEFAULT (CURRENT_DATE),
    Stato ENUM('Attiva', 'Soddisfatta', 'Cancellata') DEFAULT 'Attiva',
    FOREIGN KEY (Utente) REFERENCES Utente(ID_Utente),
    FOREIGN KEY (ISBN) REFERENCES Edizione(ISBN)
);

/* PRESTITO */
CREATE TABLE Prestito(
    ID_Prestito INT AUTO_INCREMENT PRIMARY KEY,
    Utente INT NOT NULL,
    Copia INT NOT NULL,
    Bibliotecario INT NOT NULL,
    Data_Inizio DATE DEFAULT (CURRENT_DATE),
    Data_Fine DATE NOT NULL,
    Data_Restituzione DATE NULL,
    FOREIGN KEY (Utente) REFERENCES Utente(ID_Utente),
    FOREIGN KEY (Copia) REFERENCES Copia(ID_Copia),
    FOREIGN KEY (Bibliotecario) REFERENCES Bibliotecario(ID_Bibliotecario)
);

/* ========================================================
   2. POPOLAMENTO DATI (DML) - DATASET ESTESO E COMPLETO
   ======================================================== */

-- 1. DIPARTIMENTI (Struttura invariata, 12 dipartimenti)
INSERT INTO Dipartimento (Nome, Direttore) VALUES 
('Giurisprudenza', 'Prof. Giancarlo De Vero'),
('MIFT (Matematica, Informatica, Fisica)', 'Prof. Giuseppe Maggio'),
('DICAM (Ingegneria Civile)', 'Prof. Edoardo Proverbio'),
('Economia', 'Prof. Michele Limosani'),
('Ingegneria', 'Prof.ssa Ida Milone'),
('DIMED (Medicina Clinica)', 'Prof. Giovanni Squadrito'),
('DETEV (Scienze Veterinarie)', 'Prof. Antonio Panebianco'),
('BIOMORF (Scienze Biomediche)', 'Prof. Sergio Baldari'),
('CHIBIOFARAM (Scienze Chimiche)', 'Prof. Sebastiano Campagna'),
('COSPECS (Scienze Cognitive)', 'Prof. Pietro Perconti'),
('SCIPOG (Scienze Politiche)', 'Prof. Mario Calogero'),
('Scienze Umanistiche', 'Prof. Santo Caracappa');

-- 2. SEDI BIBLIOTECA
INSERT INTO Sede (Nome, Indirizzo, Telefono) VALUES 
('Biblioteca Centrale', 'Piazza Pugliatti 1', '090-111111'),
('Polo Scientifico Papardo', 'Viale Stagno d''Alcontres', '090-222222'),
('Biblioteca Policlinico', 'Via Consolare Valeria', '090-333333'),
('Biblioteca Umanistica', 'Viale Annunziata', '090-444444');

-- 3. BIBLIOTECARI (INALTERATI)
INSERT INTO Bibliotecario (Nome, Cognome, Username, Password) VALUES 
('Luigi', 'Mario', 'admin', '$2y$10$o3JlDcVwVhtODHk6ARd71.ZpxI3JRSwK9hLg3Ibo79..HjP.nGyFC'), -- pass: admin
('Giovanni', 'Rossi', 'biblio1', '$2y$10$iKlk2NlYTWtK8PgwB0ZBzeIZKfkf8MZh1oKKJ7g2LM/fstxfm9u4u');   -- pass: user

-- 4. AUTORI (Dataset aumentato: Classici, Tecnici, Moderni)
INSERT INTO Autore (Nome, Cognome, Data_Nascita, Data_Morte) VALUES 
('Umberto', 'Eco', '1932-01-05', '2016-02-19'),           -- 1
('Italo', 'Calvino', '1923-10-15', '1985-09-19'),         -- 2
('Dante', 'Alighieri', '1265-06-01', '1321-09-14'),       -- 3
('J.K.', 'Rowling', '1965-07-31', NULL),                  -- 4
('J.R.R.', 'Tolkien', '1892-01-03', '1973-09-02'),        -- 5
('Stephen', 'King', '1947-09-21', NULL),                  -- 6
('Isaac', 'Asimov', '1920-01-02', '1992-04-06'),          -- 7
('Andrea', 'Camilleri', '1925-09-06', '2019-07-17'),      -- 8
('Alessandro', 'Manzoni', '1785-03-07', '1873-05-22'),    -- 9
('Luigi', 'Pirandello', '1867-06-28', '1936-12-10'),      -- 10
('Andrew', 'Tanenbaum', '1944-03-16', NULL),              -- 11 (Informatica)
('Bjarne', 'Stroustrup', '1950-12-30', NULL),             -- 12 (Informatica)
('Henry', 'Gray', '1827-01-01', '1861-06-13'),            -- 13 (Medicina)
('Sigmund', 'Freud', '1856-05-06', '1939-09-23'),         -- 14 (Psicologia)
('George', 'Orwell', '1903-06-25', '1950-01-21');         -- 15

-- 5. EDITORI (Aggiunti editori tecnici)
INSERT INTO Editore (Nome, Sede_Legale, Sito_Web) VALUES 
('Mondadori', 'Milano', 'www.mondadori.it'),              -- 1
('Einaudi', 'Torino', 'www.einaudi.it'),                  -- 2
('Feltrinelli', 'Milano', 'www.feltrinellieditore.it'),   -- 3
('Pearson', 'Londra', 'www.pearson.com'),                 -- 4 (Tecnico)
('Zanichelli', 'Bologna', 'www.zanichelli.it'),           -- 5 (Scolastico/Tecnico)
('O\'Reilly Media', 'California', 'www.oreilly.com'),     -- 6 (Informatica)
('Elsevier', 'Amsterdam', 'www.elsevier.com'),            -- 7 (Medicina/Scienza)
('Giuffrè', 'Milano', 'www.giuffre.it');                  -- 8 (Legge)

-- 6. CATEGORIE (Gerarchia)
INSERT INTO Categoria (Nome, Macro_Categoria) VALUES 
('Narrativa', NULL),        -- 1
('Saggistica', NULL),       -- 2
('Manuali Universitari', 2),-- 3
('Informatica', 3),         -- 4
('Medicina', 3),            -- 5
('Fantasy', 1),             -- 6
('Classici', 1),            -- 7
('Psicologia', 2),          -- 8
('Diritto', 3);             -- 9

-- 7. OPERE (Mix vario)
INSERT INTO Opera (Titolo, Anno_Pubblicazione, Lingua_Originale) VALUES 
('Il nome della rosa', 1980, 'Italiano'),                 -- 1
('Il Signore degli Anelli', 1954, 'Inglese'),             -- 2
('La Divina Commedia', 1320, 'Volgare'),                  -- 3
('Reti di Calcolatori', 2002, 'Inglese'),                 -- 4 (Tanenbaum)
('Anatomy of the Human Body', 1858, 'Inglese'),           -- 5 (Gray)
('1984', 1949, 'Inglese'),                                -- 6
('I Promessi Sposi', 1827, 'Italiano'),                   -- 7
('L\'interpretazione dei sogni', 1899, 'Tedesco'),        -- 8
('Harry Potter e la Pietra Filosofale', 1997, 'Inglese'), -- 9
('Il linguaggio C++', 1985, 'Inglese'),                   -- 10
('Uno, nessuno e centomila', 1926, 'Italiano');           -- 11

-- 8. SCRITTURA (Chi ha scritto cosa)
INSERT INTO Scrittura (Autore, Opera) VALUES 
(1,1), (5,2), (3,3), (11,4), (13,5), (15,6), (9,7), (14,8), (4,9), (12,10), (10,11);

-- 9. CLASSIFICAZIONE
INSERT INTO Classificazione (Opera, Categoria) VALUES 
(1,1), (2,6), (3,7), (4,4), (5,5), (6,1), (7,7), (8,8), (9,6), (10,4), (11,1);

-- 10. EDIZIONI (ISBN 13 Cifre senza trattini)
INSERT INTO Edizione (ISBN, Opera, Editore, Lingua_Pubblicazione, Anno_Edizione, Pagine) VALUES 
('9788845267891', 1, 1, 'Italiano', 2012, 500),  -- Nome della rosa
('9788804666666', 2, 1, 'Italiano', 2003, 1200), -- Signore Anelli
('9788807900352', 3, 3, 'Italiano', 2015, 800),  -- Divina Commedia
('9788871926499', 4, 4, 'Italiano', 2021, 900),  -- Reti (Pearson) - Molto richiesto
('9780443069529', 5, 7, 'Inglese', 2018, 1600),  -- Gray's Anatomy (Elsevier)
('9788804618252', 6, 1, 'Italiano', 2016, 330),  -- 1984
('9788806216463', 7, 2, 'Italiano', 2014, 600),  -- Promessi Sposi
('9788833923309', 8, 5, 'Italiano', 2012, 550),  -- Freud
('9788804718951', 9, 1, 'Italiano', 2020, 320),  -- Harry Potter
('9788871920787', 10, 4, 'Italiano', 2000, 700), -- C++ (Vecchio)
('9788807900406', 11, 3, 'Italiano', 2013, 220); -- Pirandello

-- 11. COPIE FISICHE (Dataset ricco per testare stati e sedi)
INSERT INTO Copia (ISBN, Sede, Stato, Data_Acquisizione) VALUES 
-- Reti di Calcolatori (Molte copie al Papardo/MIFT)
('9788871926499', 2, 'Disponibile', '2023-01-10'), -- ID 1
('9788871926499', 2, 'In prestito', '2023-01-12'), -- ID 2 (In prestito)
('9788871926499', 2, 'In prestito', '2023-01-15'), -- ID 3 (In prestito, scaduto)
('9788871926499', 1, 'Restauro',    '2022-11-20'), -- ID 4 (Rovinata)
('9788871926499', 2, 'Disponibile', '2023-09-01'), -- ID 5

-- Gray's Anatomy (Solo Policlinico)
('9780443069529', 3, 'Disponibile', '2021-05-20'), -- ID 6
('9780443069529', 3, 'Smarrito',    '2021-06-01'), -- ID 7 (Persa)

-- Harry Potter (Sparsi)
('9788804718951', 1, 'Disponibile', '2020-01-01'), -- ID 8
('9788804718951', 4, 'In prestito', '2023-10-01'), -- ID 9
('9788804718951', 4, 'Dismesso',    '2018-01-01'), -- ID 10 (Vecchia copia buttata)

-- Divina Commedia (Tante copie Umanistica)
('9788807900352', 4, 'Disponibile', '2019-05-05'), -- ID 11
('9788807900352', 4, 'Disponibile', '2019-05-05'), -- ID 12
('9788807900352', 1, 'Disponibile', '2019-05-05'), -- ID 13

-- C++ (Libro vecchio, poche copie)
('9788871920787', 2, 'Dismesso',    '2005-01-01'), -- ID 14
('9788871920787', 2, 'Disponibile', '2010-01-01'), -- ID 15

-- 1984
('9788804618252', 1, 'In prestito', '2023-11-01'); -- ID 16

-- NOTA: I Promessi Sposi e Freud NON hanno copie fisiche (Testare "0 Copie")

-- 12. UTENTI (Mix di Studenti, Prof e Personale - Tessera TESS-XXXXXX)
INSERT INTO Utente (Codice_Tessera, Nome, Cognome, Email, Telefono, Dipartimento, Tipo_Utente, Stato, Matricola, Corso_Laurea, Ufficio) VALUES 
('TESS-100001', 'Mario',    'Rossi',    'mario.rossi@studenti.unime.it',    '3331111111', 2, 'Studente',   'Attivo',  '458900', 'Informatica', NULL),
('TESS-100002', 'Luca',     'Verdi',    'luca.verdi@studenti.unime.it',     '3332222222', 2, 'Studente',   'Attivo',  '459100', 'Fisica', NULL),
('TESS-100003', 'Giulia',   'Bianchi',  'giulia.bianchi@studenti.unime.it', '3333333333', 1, 'Studente',   'Sospeso', '460200', 'Giurisprudenza', NULL), -- Sospesa
('TESS-100004', 'Marco',    'Neri',     'marco.neri@studenti.unime.it',     '3334444444', 6, 'Studente',   'Attivo',  '461300', 'Medicina', NULL),
('TESS-200001', 'Alberto',  'Angela',   'alberto.angela@unime.it',          '090123456',  12,'Professore', 'Attivo',  NULL, NULL, 'Uff. Storia'),
('TESS-200002', 'Rita',     'Levi',     'rita.levi@unime.it',               '090654321',  6, 'Professore', 'Attivo',  NULL, NULL, 'Lab. Neuro'),
('TESS-300001', 'Paolo',    'Tecnico',  'paolo.tec@admin.unime.it',         '090777666',  2, 'Personale',  'Attivo',  NULL, NULL, 'CED'),
('TESS-300002', 'Anna',     'Segretaria','anna.seg@admin.unime.it',         '090888999',  1, 'Personale',  'Attivo',  NULL, NULL, 'Segreteria');

-- 13. PRESTITI (Storico e Attivi)
-- Usiamo date relative (CURRENT_DATE) per mantenere i dati sensati nel tempo
INSERT INTO Prestito (Utente, Copia, Bibliotecario, Data_Inizio, Data_Fine, Data_Restituzione) VALUES 
-- 1. ATTIVO REGOLARE: Mario ha Reti (ID Copia 2) - Iniziato 5 gg fa
(1, 2, 1, DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 25 DAY), NULL),

-- 2. ATTIVO IN RITARDO (SCADUTO): Luca ha Reti (ID Copia 3) - Scaduto da 10 gg
(2, 3, 2, DATE_SUB(CURRENT_DATE, INTERVAL 40 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), NULL),

-- 3. ATTIVO REGOLARE: Marco ha Harry Potter (ID Copia 9)
(4, 9, 1, DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 28 DAY), NULL),

-- 4. ATTIVO REGOLARE: Prof. Angela ha 1984 (ID Copia 16)
(5, 16, 1, DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 50 DAY), NULL),

-- 5. STORICO (RESTITUITO): Mario aveva Divina Commedia
(1, 11, 1, '2023-01-01', '2023-02-01', '2023-01-20'),

-- 6. STORICO (RESTITUITO IN RITARDO): Giulia aveva C++
(3, 15, 2, '2023-05-01', '2023-06-01', '2023-06-15');

-- 14. PRENOTAZIONI
INSERT INTO Prenotazione (Utente, ISBN, Data, Stato) VALUES 
-- Giulia vuole Harry Potter (Attiva)
(3, '9788804718951', CURRENT_DATE, 'Attiva'),

-- Paolo vuole Reti di Calcolatori (Attiva, ma ce ne sono copie disponibili, utile per testare "Soddisfa")
(7, '9788871926499', DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), 'Attiva'),

-- Alberto Angela voleva Gray's Anatomy (Cancellata)
(5, '9780443069529', '2023-01-01', 'Cancellata'),

-- Rita Levi voleva Nome della Rosa (Soddisfatta -> Diventata prestito storico)
(6, '9788845267891', '2022-12-01', 'Soddisfatta');