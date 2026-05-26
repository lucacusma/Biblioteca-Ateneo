SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

DROP DATABASE IF EXISTS biblioteca_ateneo;
CREATE DATABASE biblioteca_ateneo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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
('Umberto', 'Eco', '1932-01-05', '2016-02-19'),
('Italo', 'Calvino', '1923-10-15', '1985-09-19'),
('Dante', 'Alighieri', '1265-06-01', '1321-09-14'),
('J.K.', 'Rowling', '1965-07-31', NULL),
('J.R.R.', 'Tolkien', '1892-01-03', '1973-09-02'),
('Stephen', 'King', '1947-09-21', NULL),
('Isaac', 'Asimov', '1920-01-02', '1992-04-06'),
('Andrea', 'Camilleri', '1925-09-06', '2019-07-17'),
('Alessandro', 'Manzoni', '1785-03-07', '1873-05-22'),
('Luigi', 'Pirandello', '1867-06-28', '1936-12-10'),
('Andrew', 'Tanenbaum', '1944-03-16', NULL),
('Bjarne', 'Stroustrup', '1950-12-30', NULL),
('Henry', 'Gray', '1827-01-01', '1861-06-13'),
('Sigmund', 'Freud', '1856-05-06', '1939-09-23'),
('Yuval Noah', 'Harari', '1976-02-24', NULL),
('George', 'Orwell', '1903-06-25', '1950-01-21'),
('Frank', 'Herbert', '1920-10-08', '1986-02-11'),
('Agatha', 'Christie', '1890-09-15', '1976-01-12'),
('Ken', 'Follett', '1949-06-05', NULL),
('Robert C.', 'Martin', '1952-12-05', NULL),
('Jane', 'Austen', '1775-12-16', '1817-07-18'),
('Ernest', 'Hemingway', '1899-07-21', '1961-07-02'),
('Fëdor', 'Dostoevskij', '1821-11-11', '1881-02-09'),
('Leo', 'Tolstoy', '1828-09-09', '1910-11-20'),
('Charles', 'Dickens', '1812-02-07', '1870-06-09'),
('Mark', 'Twain', '1835-11-30', '1910-04-21'),
('Virginia', 'Woolf', '1882-01-25', '1941-03-28'),
('Gabriel', 'García Márquez', '1927-03-06', '2014-04-17'),
('Haruki', 'Murakami', '1949-01-12', NULL),
('Dan', 'Brown', '1964-06-22', NULL);

-- 5. EDITORI (Aggiunti editori tecnici)
INSERT INTO Editore (Nome, Sede_Legale, Sito_Web) VALUES
('Mondadori', 'Milano', 'www.mondadori.it'),
('Einaudi', 'Torino', 'www.einaudi.it'),
('Feltrinelli', 'Milano', 'www.feltrinellieditore.it'),
('Pearson', 'Londra', 'www.pearson.com'),
('Zanichelli', 'Bologna', 'www.zanichelli.it'),
('O''Reilly Media', 'California', 'www.oreilly.com'),
('Elsevier', 'Amsterdam', 'www.elsevier.com'),
('Giuffrè', 'Milano', 'www.giuffre.it'),
('Bompiani', 'Milano', 'www.bompiani.it'),
('Adelphi', 'Milano', 'www.adelphi.it'),
('Rizzoli', 'Milano', 'www.rizzolilibri.it'),
('Laterza', 'Bari', 'www.laterza.it'),
('Newton Compton', 'Roma', 'www.newtoncompton.com'),
('Garzanti', 'Milano', 'www.garzanti.it'),
('Sellerio', 'Palermo', 'www.sellerio.it'),
('Hoepli', 'Milano', 'www.hoepli.it'),
('McGraw-Hill', 'New York', 'www.mheducation.com'),
('Springer', 'Berlino', 'www.springer.com');

-- 6. CATEGORIE (Gerarchia)
INSERT INTO Categoria (Nome, Macro_Categoria) VALUES
('Narrativa', NULL),        -- ID 1
('Saggistica', NULL),       -- ID 2
('Scienze', NULL),          -- ID 3
('Manuali e Didattica', NULL), -- ID 4
('Classici', 1),            -- ID 5
('Fantasy', 1),             -- ID 6
('Fantascienza', 1),        -- ID 7
('Giallo', 1),              -- ID 8
('Horror', 1),              -- ID 9
('Thriller', 1),            -- ID 10
('Romanzo Storico', 1),     -- ID 11
('Avventura', 1),           -- ID 12
('Poesia', 1),              -- ID 13
('Distopico', 1),           -- ID 14
('Umoristico', 1),          -- ID 15
('Psicologia', 2),          -- ID 16
('Filosofia', 2),           -- ID 17
('Storia', 2),              -- ID 18
('Attualità', 2),           -- ID 19
('Informatica', 3),         -- ID 20
('Medicina', 3),            -- ID 21
('Matematica', 3),          -- ID 22
('Fisica', 3),              -- ID 23
('Tecnologia', 4);          -- ID 24

-- 7. OPERE (Mix vario)
INSERT INTO Opera (Titolo, Anno_Pubblicazione, Lingua_Originale) VALUES
('Il nome della rosa', 1980, 'Italiano'),
('Il pendolo di Foucault', 1988, 'Italiano'),
('Il barone rampante', 1957, 'Italiano'),
('Le città invisibili', 1972, 'Italiano'),
('La Divina Commedia', 1320, 'Volgare'),
('Vita Nuova', 1294, 'Volgare'),
('Harry Potter e la Pietra Filosofale', 1997, 'Inglese'),
('Harry Potter e i Doni della Morte', 2007, 'Inglese'),
('Il Signore degli Anelli', 1954, 'Inglese'),
('Lo Hobbit', 1937, 'Inglese'),
('Shining', 1977, 'Inglese'),
('It', 1986, 'Inglese'),
('Io, Robot', 1950, 'Inglese'),
('Fondazione', 1951, 'Inglese'),
('La forma dell''acqua', 1994, 'Italiano'),
('Il cane di terracotta', 1996, 'Italiano'),
('I Promessi Sposi', 1827, 'Italiano'),
('Il fu Mattia Pascal', 1904, 'Italiano'),
('Uno, nessuno e centomila', 1926, 'Italiano'),
('Reti di Calcolatori', 2002, 'Inglese'),
('Modern Operating Systems', 2001, 'Inglese'),
('Il linguaggio C++', 1985, 'Inglese'),
('Anatomy of the Human Body', 1858, 'Inglese'),
('L''interpretazione dei sogni', 1899, 'Tedesco'),
('Sapiens: Da animali a dèi', 2011, 'Ebraico'),
('Homo Deus', 2015, 'Ebraico'),
('1984', 1949, 'Inglese'),
('La fattoria degli animali', 1945, 'Inglese'),
('Dune', 1965, 'Inglese'),
('Messia di Dune', 1969, 'Inglese'),
('Assassinio sull''Orient Express', 1934, 'Inglese'),
('Dieci piccoli indiani', 1939, 'Inglese'),
('I pilastri della terra', 1989, 'Inglese'),
('Clean Code', 2008, 'Inglese'),
('Orgoglio e pregiudizio', 1813, 'Inglese'),
('Il vecchio e il mare', 1952, 'Inglese'),
('Delitto e castigo', 1866, 'Russo'),
('Guerra e pace', 1869, 'Russo'),
('Oliver Twist', 1838, 'Inglese'),
('Le avventure di Tom Sawyer', 1876, 'Inglese'),
('Gita al faro', 1927, 'Inglese'),
('Cent''anni di solitudine', 1967, 'Spagnolo'),
('Kafka sulla spiaggia', 2002, 'Giapponese'),
('Il codice da Vinci', 2003, 'Inglese'),
('Design Patterns', 1994, 'Inglese');

-- 8. SCRITTURA (Chi ha scritto cosa)
INSERT INTO Scrittura (Autore, Opera) VALUES
(1, 1), (1, 2), (2, 3), (2, 4), (3, 5), (3, 6), (4, 7), (4, 8), (5, 9), (5, 10),
(6, 11), (6, 12), (7, 13), (7, 14), (8, 15), (8, 16), (9, 17), (10, 18), (10, 19),
(11, 20), (11, 21), (12, 22), (13, 23), (14, 24), (15, 25), (15, 26), (16, 27),
(16, 28), (17, 29), (17, 30), (18, 31), (18, 32), (19, 33), (20, 34), (21, 35),
(22, 36), (23, 37), (24, 38), (25, 39), (26, 40), (27, 41), (28, 42), (29, 43),
(30, 44), (11, 45), (12, 45), (20, 45);

-- 9. CLASSIFICAZIONE
INSERT INTO Classificazione (Opera, Categoria) VALUES
(1, 11), (1, 8),   -- Nome della rosa: Storico, Giallo
(2, 10),           -- Pendolo: Thriller
(3, 5), (3, 12),   -- Barone rampante: Classici, Avventura
(4, 5), (4, 6),    -- Città invisibili: Classici, Fantasy
(5, 5), (5, 13),   -- Divina Commedia: Classici, Poesia
(6, 5), (6, 13),   -- Vita Nuova: Classici, Poesia
(7, 6), (7, 12),   -- HP 1: Fantasy, Avventura
(8, 6), (8, 12),   -- HP 7: Fantasy, Avventura
(9, 6), (9, 12),   -- Signore Anelli: Fantasy, Avventura
(10, 6), (10, 12), -- Hobbit: Fantasy, Avventura
(11, 9), (11, 10), -- Shining: Horror, Thriller
(12, 9), (12, 10), -- It: Horror, Thriller
(13, 7),           -- Io, Robot: Fantascienza
(14, 7),           -- Fondazione: Fantascienza
(15, 8),           -- Forma acqua: Giallo
(16, 8),           -- Cane terracotta: Giallo
(17, 5), (17, 11), -- Promessi Sposi: Classici, Storico
(18, 5),           -- Mattia Pascal: Classici
(19, 5),           -- Uno, nessuno: Classici
(20, 20), (20, 24),-- Reti: Informatica, Tecnologia
(21, 20), (21, 24),-- OS: Informatica, Tecnologia
(22, 20), (22, 24),-- C++: Informatica, Tecnologia
(23, 21), (23, 24),-- Gray Anatomy: Medicina, Tecnologia
(24, 16), (24, 2), -- Freud: Psicologia, Saggistica
(25, 18), (25, 2), -- Sapiens: Storia, Saggistica
(26, 17), (26, 2), -- Homo Deus: Filosofia, Saggistica
(27, 7), (27, 14), (27, 5), -- 1984: Scifi, Distopico, Classici
(28, 14), (28, 5), -- Fattoria: Distopico, Classici
(29, 7), (29, 12), -- Dune: Scifi, Avventura
(30, 7),           -- Messia Dune: Scifi
(31, 8),           -- Orient Express: Giallo
(32, 8), (32, 10), -- Dieci piccoli: Giallo, Thriller
(33, 11), (33, 10),-- Pilastri terra: Storico, Thriller
(34, 20), (34, 24),-- Clean Code: Informatica, Tecnologia
(35, 5), (35, 11), -- Orgoglio e P.: Classici, Storico
(36, 5), (36, 12), -- Vecchio e mare: Classici, Avventura
(37, 5), (37, 10), -- Delitto e castigo: Classici, Thriller
(38, 5), (38, 11), -- Guerra e pace: Classici, Storico
(39, 5),           -- Oliver Twist: Classici
(40, 5), (40, 12), -- Tom Sawyer: Classici, Avventura
(41, 5),           -- Gita al faro: Classici
(42, 5), (42, 6),  -- Cent'anni: Classici, Fantasy
(43, 6), (43, 10), -- Kafka sulla spiaggia: Fantasy, Thriller
(44, 10), (44, 8), -- Codice Da Vinci: Thriller, Giallo
(45, 20), (45, 24);-- Design Patterns: Informatica, Tecnologia

-- 10. EDIZIONI (ISBN 13 Cifre senza trattini)
INSERT INTO Edizione (ISBN, Opera, Editore, Lingua_Pubblicazione, Anno_Edizione, Pagine) VALUES
('9780666005166', 41, 3, 'Italiano', 1999, 363),
('9785069962117', 29, 12, 'Inglese', 2013, 359),
('9785413328545', 7, 3, 'Italiano', 2006, 1097),
('9789025795240', 14, 2, 'Italiano', 1984, 724),
('9783042964011', 37, 14, 'Italiano', 1907, 155),
('9788602411225', 1, 8, 'Italiano', 2006, 208),
('9789392727569', 31, 14, 'Inglese', 2007, 523),
('9785345733756', 38, 1, 'Italiano', 1999, 1012),
('9780160242971', 13, 10, 'Inglese', 1952, 903),
('9788464316098', 3, 12, 'Italiano', 1992, 609),
('9780480098206', 9, 13, 'Italiano', 2021, 403),
('9780247420753', 15, 15, 'Italiano', 2023, 605),
('9788502731324', 6, 14, 'Italiano', 1577, 1131),
('9782405334790', 2, 8, 'Italiano', 1999, 489),
('9788289748764', 27, 3, 'Italiano', 2000, 767),
('9783941557686', 25, 11, 'Italiano', 2012, 789),
('9781106688580', 10, 1, 'Italiano', 1937, 175),
('9788334961646', 32, 11, 'Italiano', 1989, 192),
('9785258518352', 21, 17, 'Inglese', 2017, 163),
('9787621013763', 34, 4, 'Italiano', 2024, 312),
('9788111738580', 33, 14, 'Italiano', 2024, 588),
('9781459232936', 18, 13, 'Italiano', 2013, 694),
('9781410082245', 22, 17, 'Italiano', 1985, 674),
('9781101879704', 39, 1, 'Italiano', 2006, 987),
('9787640357843', 36, 10, 'Italiano', 1999, 537),
('9780132080449', 44, 1, 'Italiano', 2018, 1039),
('9786049787799', 11, 3, 'Inglese', 2007, 899),
('9784296489690', 20, 5, 'Italiano', 2016, 395),
('9785622895655', 42, 12, 'Italiano', 1998, 1089),
('9787040621072', 30, 12, 'Inglese', 1990, 429),
('9780566097581', 45, 5, 'Italiano', 2023, 1064),
('9789529540815', 16, 11, 'Italiano', 2000, 366),
('9789949958036', 19, 15, 'Italiano', 1958, 1120),
('9783275263912', 4, 3, 'Italiano', 2024, 580),
('9788009487553', 12, 12, 'Inglese', 1992, 684),
('9782074665755', 8, 10, 'Italiano', 2015, 994),
('9789955222340', 35, 1, 'Inglese', 2000, 648),
('9783595971342', 17, 8, 'Italiano', 1867, 543),
('9782808667073', 43, 15, 'Italiano', 2024, 1109),
('9781638844969', 24, 12, 'Italiano', 1915, 354),
('9780799154806', 26, 3, 'Italiano', 2024, 1111),
('9781804875229', 28, 8, 'Italiano', 1983, 941),
('9783334159970', 15, 11, 'Italiano', 2013, 1009),
('9785046070853', 24, 12, 'Italiano', 1974, 949),
('9785056337630', 27, 15, 'Inglese', 1955, 1190),
('9781060041725', 20, 18, 'Inglese', 2011, 172),
('9788369464699', 3, 2, 'Italiano', 1999, 577),
('9782105367579', 25, 15, 'Italiano', 2014, 695),
('9781994474050', 17, 11, 'Italiano', 1887, 975),
('9782088716630', 32, 8, 'Italiano', 1966, 503),
('9784715741223', 39, 8, 'Italiano', 1988, 287),
('9784886297836', 26, 10, 'Italiano', 2023, 784),
('9780152531617', 1, 12, 'Italiano', 2010, 786),
('9785959191661', 17, 3, 'Italiano', 1834, 885),
('9785841506533', 1, 14, 'Italiano', 2000, 711);

-- 11. COPIE FISICHE (Dataset ricco per testare stati e sedi)
INSERT INTO Copia (ISBN, Sede, Stato, Data_Acquisizione) VALUES 
('9780666005166', 1, 'Dismesso', '2016-01-24'),
('9785069962117', 3, 'Disponibile', '2016-01-06'),
('9785413328545', 4, 'Dismesso', '2021-09-04'),
('9789025795240', 2, 'Disponibile', '2024-01-28'),
('9783042964011', 4, 'Disponibile', '2021-12-05'),
('9788602411225', 4, 'In prestito', '2021-02-26'),
('9789392727569', 1, 'Disponibile', '2018-03-23'),
('9785345733756', 4, 'Disponibile', '2015-07-05'),
('9780160242971', 1, 'In prestito', '2012-10-11'),
('9788464316098', 3, 'Disponibile', '2022-09-06'),
('9780480098206', 3, 'In prestito', '2023-02-16'),
('9780247420753', 1, 'Disponibile', '2014-12-05'),
('9788502731324', 3, 'Disponibile', '2021-05-06'),
('9782405334790', 3, 'Disponibile', '2011-01-17'),
('9788289748764', 4, 'Dismesso', '2023-08-09'),
('9783941557686', 2, 'Disponibile', '2013-08-19'),
('9781106688580', 2, 'Disponibile', '2017-11-26'),
('9788334961646', 2, 'Disponibile', '2021-05-11'),
('9785258518352', 3, 'Disponibile', '2019-11-08'),
('9787621013763', 1, 'Restauro', '2017-04-02'),
('9788111738580', 3, 'Disponibile', '2011-08-06'),
('9781459232936', 1, 'Restauro', '2013-10-11'),
('9781410082245', 2, 'In prestito', '2019-06-09'),
('9781101879704', 3, 'In prestito', '2012-05-26'),
('9787640357843', 3, 'Restauro', '2019-01-29'),
('9780132080449', 2, 'Disponibile', '2016-09-26'),
('9786049787799', 1, 'Disponibile', '2023-06-18'),
('9784296489690', 2, 'In prestito', '2023-06-11'),
('9785622895655', 2, 'Disponibile', '2022-09-24'),
('9787040621072', 3, 'Smarrito', '2019-12-09'),
('9780566097581', 4, 'Disponibile', '2014-05-29'),
('9789529540815', 2, 'Disponibile', '2017-03-17'),
('9789949958036', 3, 'Disponibile', '2020-04-01'),
('9783275263912', 4, 'Disponibile', '2017-09-29'),
('9788009487553', 1, 'Restauro', '2022-12-27'),
('9782074665755', 3, 'Disponibile', '2018-12-01'),
('9789955222340', 4, 'Disponibile', '2011-03-30'),
('9783595971342', 3, 'Disponibile', '2010-05-01'),
('9782808667073', 1, 'Smarrito', '2010-05-15'),
('9781638844969', 3, 'Dismesso', '2024-11-12'),
('9780799154806', 1, 'In prestito', '2010-11-22'),
('9781804875229', 4, 'Disponibile', '2020-06-19'),
('9783334159970', 2, 'Smarrito', '2012-02-22'),
('9785046070853', 3, 'Disponibile', '2023-05-18'),
('9785056337630', 4, 'Dismesso', '2022-06-17'),
('9781060041725', 2, 'Disponibile', '2020-06-02'),
('9788369464699', 4, 'Disponibile', '2014-07-10'),
('9782105367579', 1, 'Disponibile', '2020-09-15'),
('9781994474050', 1, 'Disponibile', '2018-09-08'),
('9782088716630', 3, 'Disponibile', '2024-09-27'),
('9784715741223', 4, 'Disponibile', '2010-08-13'),
('9784886297836', 2, 'Dismesso', '2024-09-07'),
('9780152531617', 1, 'Disponibile', '2017-05-08'),
('9785959191661', 3, 'Dismesso', '2020-10-06'),
('9785841506533', 1, 'Disponibile', '2017-01-01'),
('9788464316098', 4, 'Disponibile', '2024-09-30'),
('9788502731324', 4, 'In prestito', '2022-12-06'),
('9785959191661', 2, 'Disponibile', '2013-06-17'),
('9789529540815', 4, 'In prestito', '2022-11-20'),
('9781060041725', 3, 'Dismesso', '2011-05-23'),
('9782074665755', 3, 'In prestito', '2011-01-06'),
('9780480098206', 2, 'Disponibile', '2017-12-06'),
('9783595971342', 1, 'In prestito', '2021-05-09'),
('9788111738580', 4, 'Disponibile', '2013-06-29'),
('9782405334790', 3, 'Disponibile', '2023-12-19'),
('9789025795240', 4, 'In prestito', '2024-03-24'),
('9785056337630', 4, 'Disponibile', '2024-08-04'),
('9784886297836', 1, 'Restauro', '2024-05-16'),
('9783334159970', 3, 'Disponibile', '2021-06-16'),
('9780152531617', 3, 'Restauro', '2016-06-01'),
('9780152531617', 1, 'In prestito', '2018-12-07'),
('9789025795240', 2, 'Restauro', '2014-11-19'),
('9780566097581', 1, 'Disponibile', '2022-03-16'),
('9781060041725', 4, 'Disponibile', '2018-06-24'),
('9784886297836', 3, 'Smarrito', '2024-11-30');

-- 12. UTENTI (Mix di Studenti, Prof e Personale - Tessera TESS-XXXXXX)
INSERT INTO Utente (Codice_Tessera, Nome, Cognome, Email, Telefono, Dipartimento, Tipo_Utente, Stato, Matricola, Corso_Laurea, Ufficio) VALUES 
('TESS-C23D2A', 'Andrea', 'Martinelli', 'andrea.martinelli@unime.it', '3341450381', 3, 'Professore', 'Attivo', NULL, NULL, 'Uff. Amministrazione'),
('TESS-3BE70C', 'Vincenzo', 'Galli', 'vincenzo.galli@studenti.unime.it', '3369501538', 2, 'Studente', 'Attivo', '458506', 'Filosofia', NULL),
('TESS-7255B3', 'Francesco', 'Rossi', 'francesco.rossi@studenti.unime.it', '3375278596', 9, 'Studente', 'Attivo', '586013', 'Matematica', NULL),
('TESS-5681BC', 'Valentina', 'Vitale', 'valentina.vitale@unime.it', '3374760050', 4, 'Professore', 'Attivo', NULL, NULL, 'Dip. Ricerca'),
('TESS-EFC482', 'Vincenzo', 'Marchetti', 'vincenzo.marchetti@studenti.unime.it', '3361708351', 12, 'Studente', 'Attivo', '486813', 'Giurisprudenza', NULL),
('TESS-8E7B12', 'Enrico', 'Santoro', 'enrico.santoro@studenti.unime.it', '3311121017', 2, 'Studente', 'Attivo', '516234', 'Lettere', NULL),
('TESS-26F140', 'Aurora', 'Bruno', 'aurora.bruno@studenti.unime.it', '3326865036', 12, 'Studente', 'Attivo', '544600', 'Ingegneria Civile', NULL),
('TESS-E4778B', 'Mattia', 'De Luca', 'mattia.deluca@studenti.unime.it', '3318413281', 7, 'Studente', 'Attivo', '536010', 'Matematica', NULL),
('TESS-064FDE', 'Giovanni', 'Lombardi', 'giovanni.lombardi@studenti.unime.it', '3351956168', 6, 'Studente', 'Attivo', '587319', 'Scienze della Formazione', NULL),
('TESS-2A59C2', 'Ludovica', 'Marino', 'ludovica.marino@studenti.unime.it', '3377269206', 11, 'Studente', 'Attivo', '513540', 'Ingegneria Informatica', NULL),
('TESS-0932B8', 'Ginevra', 'Ferri', 'ginevra.ferri@unime.it', '3366651899', 4, 'Personale', 'Attivo', NULL, NULL, 'Uff. Amministrazione'),
('TESS-960307', 'Edoardo', 'Moretti', 'edoardo.moretti@studenti.unime.it', '3359776322', 12, 'Studente', 'Sospeso', '489174', 'Ingegneria Informatica', NULL),
('TESS-CD4C34', 'Giulia', 'Conte', 'giulia.conte@studenti.unime.it', '3381280599', 10, 'Studente', 'Attivo', '547990', 'Medicina', NULL),
('TESS-9F8838', 'Giovanni', 'Costa', 'giovanni.costa@studenti.unime.it', '3310023838', 8, 'Studente', 'Attivo', '446369', 'Biologia', NULL),
('TESS-52E861', 'Ginevra', 'Ferrari', 'ginevra.ferrari@studenti.unime.it', '3364584088', 8, 'Studente', 'Attivo', '442755', 'Medicina', NULL),
('TESS-B87C81', 'Federico', 'Vitale', 'federico.vitale@studenti.unime.it', '3376800353', 8, 'Studente', 'Attivo', '576895', 'Scienze Politiche', NULL),
('TESS-FA4817', 'Laura', 'Valentini', 'laura.valentini@unime.it', '3393802646', 1, 'Personale', 'Attivo', NULL, NULL, 'Dip. Ricerca'),
('TESS-CA496B', 'Leonardo', 'Marchetti', 'leonardo.marchetti@studenti.unime.it', '3344806614', 7, 'Studente', 'Attivo', '529469', 'Economia', NULL),
('TESS-EFD47E', 'Stefano', 'Marchetti', 'stefano.marchetti@studenti.unime.it', '3340927013', 6, 'Studente', 'Attivo', '487130', 'Ingegneria Civile', NULL),
('TESS-CBA274', 'Valentina', 'Grasso', 'valentina.grasso@unime.it', '3311807066', 5, 'Personale', 'Attivo', NULL, NULL, 'Lab. B'),
('TESS-CB4DC1', 'Ginevra', 'Ferri', 'ginevra.ferri1@unime.it', '3342171954', 6, 'Personale', 'Attivo', NULL, NULL, 'Uff. Amministrazione'),
('TESS-738EE3', 'Matteo', 'Marchetti', 'matteo.marchetti@studenti.unime.it', '3392477655', 8, 'Studente', 'Attivo', '559934', 'Fisica', NULL),
('TESS-FC47F9', 'Giulia', 'Costa', 'giulia.costa@unime.it', '3331256699', 2, 'Professore', 'Attivo', NULL, NULL, 'Uff. Tecnico'),
('TESS-3FD6F8', 'Giulia', 'Lombardi', 'giulia.lombardi@studenti.unime.it', '3316444241', 2, 'Studente', 'Attivo', '562882', 'Scienze della Formazione', NULL),
('TESS-FBE5A8', 'Antonio', 'Ferrari', 'antonio.ferrari@studenti.unime.it', '3388942505', 5, 'Studente', 'Attivo', '573059', 'Ingegneria Informatica', NULL),
('TESS-5866CA', 'Simone', 'Bruno', 'simone.bruno@studenti.unime.it', '3380533900', 1, 'Studente', 'Attivo', '429877', 'Fisica', NULL),
('TESS-277504', 'Aurora', 'Parisi', 'aurora.parisi@unime.it', '3332128235', 6, 'Professore', 'Attivo', NULL, NULL, 'Dip. Ricerca'),
('TESS-8F545A', 'Francesca', 'Grasso', 'francesca.grasso@studenti.unime.it', '3333826210', 6, 'Studente', 'Attivo', '597430', 'Ingegneria Civile', NULL),
('TESS-63A3CF', 'Davide', 'Fontana', 'davide.fontana@studenti.unime.it', '3384907100', 9, 'Studente', 'Attivo', '520888', 'Giurisprudenza', NULL),
('TESS-6D64AA', 'Alessandro', 'Lombardo', 'alessandro.lombardo@studenti.unime.it', '3331198383', 4, 'Studente', 'Attivo', '455407', 'Fisica', NULL),
('TESS-799220', 'Lorenzo', 'Mariani', 'lorenzo.mariani@studenti.unime.it', '3398919836', 6, 'Studente', 'Attivo', '582474', 'Lettere', NULL),
('TESS-118A0F', 'Giovanni', 'Russo', 'giovanni.russo@studenti.unime.it', '3333570004', 4, 'Studente', 'Attivo', '550437', 'Informatica', NULL),
('TESS-0FC89E', 'Andrea', 'Marino', 'andrea.marino@studenti.unime.it', '3312618195', 10, 'Studente', 'Attivo', '422853', 'Scienze Politiche', NULL),
('TESS-14442C', 'Edoardo', 'Conte', 'edoardo.conte@unime.it', '3317536999', 2, 'Professore', 'Attivo', NULL, NULL, 'Lab. B'),
('TESS-A0F6DD', 'Leonardo', 'Bianchi', 'leonardo.bianchi@unime.it', '3326297586', 9, 'Personale', 'Attivo', NULL, NULL, 'Uff. Amministrazione'),
('TESS-2AC332', 'Tommaso', 'Martini', 'tommaso.martini@studenti.unime.it', '3335078037', 6, 'Studente', 'Attivo', '558067', 'Matematica', NULL),
('TESS-CB0090', 'Leonardo', 'Rinaldi', 'leonardo.rinaldi@unime.it', '3321366276', 7, 'Professore', 'Attivo', NULL, NULL, 'Dip. Ricerca'),
('TESS-3FAE8B', 'Federico', 'Villa', 'federico.villa@studenti.unime.it', '3374604427', 8, 'Studente', 'Attivo', '408758', 'Fisica', NULL),
('TESS-1AF55E', 'Sofia', 'Rossi', 'sofia.rossi@unime.it', '3363298960', 10, 'Personale', 'Attivo', NULL, NULL, 'Segreteria'),
('TESS-A5E3A9', 'Sofia', 'Greco', 'sofia.greco@unime.it', '3335555818', 8, 'Professore', 'Attivo', NULL, NULL, 'Uff. Amministrazione'),
('TESS-02652A', 'Giulia', 'Caruso', 'giulia.caruso@unime.it', '3366463748', 3, 'Professore', 'Attivo', NULL, NULL, 'Lab. B'),
('TESS-5390F3', 'Giorgia', 'Villa', 'giorgia.villa@studenti.unime.it', '3380128080', 4, 'Studente', 'Attivo', '469988', 'Economia', NULL),
('TESS-A68E69', 'Davide', 'Romano', 'davide.romano@studenti.unime.it', '3312280508', 8, 'Studente', 'Attivo', '456649', 'Informatica', NULL),
('TESS-EA1676', 'Paolo', 'Romano', 'paolo.romano@unime.it', '3344570560', 5, 'Personale', 'Sospeso', NULL, NULL, 'Dip. Ricerca'),
('TESS-203273', 'Chiara', 'Bianchi', 'chiara.bianchi@studenti.unime.it', '3341722855', 9, 'Studente', 'Attivo', '536379', 'Giurisprudenza', NULL),
('TESS-F36186', 'Chiara', 'Serra', 'chiara.serra@unime.it', '3312556557', 3, 'Professore', 'Attivo', NULL, NULL, 'Uff. Amministrazione'),
('TESS-AABDCD', 'Aurora', 'Gentile', 'aurora.gentile@studenti.unime.it', '3353957555', 7, 'Studente', 'Attivo', '500127', 'Chimica', NULL),
('TESS-28C788', 'Elisa', 'Romano', 'elisa.romano@studenti.unime.it', '3326828216', 8, 'Studente', 'Attivo', '447591', 'Economia', NULL),
('TESS-E2A65F', 'Vittoria', 'Rizzo', 'vittoria.rizzo@unime.it', '3386602573', 10, 'Professore', 'Attivo', NULL, NULL, 'Segreteria'),
('TESS-FD894E', 'Sofia', 'Bruno', 'sofia.bruno@unime.it', '3337417943', 1, 'Personale', 'Attivo', NULL, NULL, 'Lab. B');

-- 13. PRESTITI (Storico e Attivi)
INSERT INTO Prestito (Utente, Copia, Bibliotecario, Data_Inizio, Data_Fine, Data_Restituzione) VALUES 
-- --- ANNO 2021 (Storico, tutti conclusi) ---
(5, 12, 1, '2021-02-10', '2021-03-12', '2021-03-10'), -- Restituito in tempo
(12, 45, 2, '2021-03-15', '2021-04-14', '2021-04-20'), -- Restituito in ritardo
(33, 2, 1, '2021-04-01', '2021-05-01', '2021-04-28'),
(7, 60, 2, '2021-05-20', '2021-06-19', '2021-06-15'),
(41, 15, 1, '2021-09-10', '2021-10-10', '2021-10-05'),
(2, 22, 1, '2021-10-05', '2021-11-04', '2021-11-01'),
(19, 30, 2, '2021-11-12', '2021-12-12', '2021-12-10'),
(25, 7, 1, '2021-12-01', '2021-12-31', '2022-01-05'), -- Scavallamento anno, ritardo

-- --- ANNO 2022 (Storico) ---
(10, 55, 2, '2022-01-15', '2022-02-14', '2022-02-10'),
(4, 9, 1, '2022-02-20', '2022-03-22', '2022-03-20'),
(48, 71, 1, '2022-03-10', '2022-04-09', '2022-04-01'),
(15, 33, 2, '2022-04-05', '2022-05-05', '2022-05-15'), -- Ritardo
(22, 18, 1, '2022-05-12', '2022-06-11', '2022-06-10'),
(9, 40, 2, '2022-06-20', '2022-07-20', '2022-07-18'),
(30, 25, 1, '2022-09-01', '2022-10-01', '2022-09-28'),
(3, 3, 2, '2022-09-15', '2022-10-15', '2022-10-15'),
(44, 11, 1, '2022-10-10', '2022-11-09', '2022-11-05'),
(17, 66, 2, '2022-11-05', '2022-12-05', '2022-12-01'),
(28, 50, 1, '2022-12-10', '2023-01-09', '2023-01-08'),

-- --- ANNO 2023 (Storico) ---
(1, 1, 1, '2023-01-20', '2023-02-19', '2023-02-15'),
(38, 75, 2, '2023-02-10', '2023-03-12', '2023-03-10'),
(6, 14, 1, '2023-03-05', '2023-04-04', '2023-04-01'),
(45, 29, 2, '2023-04-15', '2023-05-15', '2023-05-20'), -- Ritardo
(14, 5, 1, '2023-05-10', '2023-06-09', '2023-06-05'),
(29, 62, 2, '2023-06-01', '2023-07-01', '2023-06-30'),
(40, 48, 1, '2023-09-12', '2023-10-12', '2023-10-10'),
(11, 21, 2, '2023-10-05', '2023-11-04', '2023-11-01'),
(23, 38, 1, '2023-10-20', '2023-11-19', '2023-11-15'),
(50, 10, 2, '2023-11-15', '2023-12-15', '2023-12-10'),
(8, 58, 1, '2023-12-05', '2024-01-04', '2024-01-03'),

-- --- ANNO 2024 (Storico recente) ---
(35, 13, 2, '2024-01-15', '2024-02-14', '2024-02-12'),
(20, 4, 1, '2024-02-10', '2024-03-11', '2024-03-09'),
(42, 70, 2, '2024-03-01', '2024-03-31', '2024-04-05'), -- Ritardo
(16, 26, 1, '2024-04-10', '2024-05-10', '2024-05-08'),
(27, 44, 1, '2024-05-05', '2024-06-04', '2024-06-01'),
(13, 19, 2, '2024-06-15', '2024-07-15', '2024-07-15'),
(47, 35, 1, '2024-09-10', '2024-10-10', '2024-10-08'),
(31, 52, 2, '2024-09-25', '2024-10-25', '2024-10-20'),
(18, 65, 1, '2024-10-15', '2024-11-14', '2024-11-10'),
(5, 72, 2, '2024-11-01', '2024-12-01', '2024-11-28'),
(36, 8, 1, '2024-11-20', '2024-12-20', '2024-12-19'),
(24, 28, 2, '2024-12-01', '2024-12-31', '2025-01-05'), -- Scavallamento anno

-- --- ANNO 2025 (Anno scorso) ---
(39, 42, 1, '2025-01-10', '2025-02-09', '2025-02-05'),
(43, 6, 2, '2025-02-15', '2025-03-17', '2025-03-15'),
(26, 55, 1, '2025-03-10', '2025-04-09', '2025-04-10'),
(21, 31, 2, '2025-04-05', '2025-05-05', '2025-05-01'),
(46, 17, 1, '2025-05-20', '2025-06-19', '2025-06-15'),
(12, 49, 2, '2025-06-10', '2025-07-10', '2025-07-12'),
(2, 23, 1, '2025-09-05', '2025-10-05', '2025-10-01'),
(49, 68, 2, '2025-09-20', '2025-10-20', '2025-10-18'),
(34, 37, 1, '2025-10-10', '2025-11-09', '2025-11-05'),
(19, 56, 2, '2025-11-01', '2025-12-01', '2025-11-30'),

-- --- PRESTITI ATTIVI / IN CORSO (Data_Restituzione IS NULL) ---
-- Situazione corrente: Gennaio 2026

-- Prestiti recenti (ancora nei termini)
(10, 64, 1, '2025-12-15', '2026-01-14', NULL), -- L'amica geniale
(7, 20, 2, '2025-12-20', '2026-01-19', NULL), -- Nome della Rosa
(15, 69, 1, '2025-12-28', '2026-01-27', NULL), -- Gomorra
(32, 24, 2, '2026-01-02', '2026-02-01', NULL), -- Harry Potter
(25, 41, 1, '2026-01-05', '2026-02-04', NULL), -- Grande Gatsby
(41, 60, 2, '2026-01-07', '2026-02-06', NULL), -- Design Patterns
(8, 58, 1, '2026-01-08', '2026-02-07', NULL), -- Clean Code
(37, 32, 2, '2026-01-09', '2026-02-08', NULL), -- Signore degli Anelli
(22, 16, 1, '2026-01-10', '2026-02-09', NULL), -- 1984

-- Prestiti in SCADENZA o APPENA SCADUTI
(4, 5, 2, '2025-12-10', '2026-01-09', NULL), -- Divina Commedia (Scaduto ieri)
(30, 51, 1, '2025-12-05', '2026-01-04', NULL), -- Delitto e castigo (Scaduto da una settimana)

-- Prestiti in GRAVE RITARDO (Mai restituiti)
(14, 74, 1, '2025-09-15', '2025-10-15', NULL), -- Clean Code (Perso?)
(44, 27, 2, '2025-06-01', '2025-07-01', NULL), -- Harry Potter (Studente non risponde)
(3, 39, 1, '2025-05-10', '2025-06-09', NULL), -- Orgoglio e Pregiudizio
(20, 11, 2, '2025-10-01', '2025-10-31', NULL); -- Promessi Sposi

-- 14. PRENOTAZIONI
INSERT INTO Prenotazione (Utente, ISBN, Data, Stato) VALUES 
-- Prenotazioni per il primo libro molto richiesto (sostituisce Reti di Calcolatori)
(13, '9780666005166', '2026-01-09', 'Attiva'),
(28, '9780666005166', '2026-01-10', 'Attiva'),
(45, '9780666005166', '2026-01-11', 'Attiva'),

-- Prenotazioni per il secondo libro (sostituisce Harry Potter)
(2, '9785413328545', '2026-01-05', 'Attiva'),
(19, '9785413328545', '2026-01-08', 'Attiva'),

-- Prenotazioni per il terzo libro (sostituisce Clean Code)
(31, '9789025795240', '2026-01-02', 'Attiva'),
(9, '9789025795240', '2026-01-07', 'Attiva'),

-- Prenotazioni per il quarto libro (sostituisce Gray's Anatomy)
(14, '9783042964011', '2026-01-04', 'Attiva'),
(15, '9783042964011', '2026-01-10', 'Attiva'),

-- Prenotazione per il quinto libro (sostituisce Nome della Rosa)
(40, '9788602411225', '2025-12-28', 'Attiva'),

-- Esempio di prenotazioni gestite (Stato diverso da Attiva)
(5, '9789392727569', '2025-12-15', 'Soddisfatta'), -- L'utente ha poi preso il libro
(22, '9785345733756', '2025-11-20', 'Cancellata'); -- L'utente ha rinunciato