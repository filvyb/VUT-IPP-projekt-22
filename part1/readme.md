Implementační dokumentace k 1. úloze do IPP 2021/2022
Jméno a příjmení: Filip Vybíhal
Login: xvybih06

## Analyzátor zdrojového kódu IPPcode22 (parse.php)
Skript parse.php přijímá na standardním vstupu či čte ze souborů argumentem `--source=` zdrojový kód psaný v jazyce IPPcode22 provede syntaktickou a lexikální analýzu a na standardní výstpu vypíše XML reprezentaci programu jazyka IPPcode22. 

Základem celého skriptu je funkce `do_work`, která bere cestu k souboru či nulljako argument. Pokud dostane null, čte z STDIN.

### Design scriptu
Script jsem se snažil psát aby byl lehce čitelný a samodokumentující se. Proto nepoužívám OOP, jelikož by podle mého názoru zkomplikovalo u tohoto typu scriptu vývoj i čitelnost. Seznam podporovaných instrukcí IPPcode22 je definovaný spolu s podmínkami v jednom velkém switch case uvnitř funkce `do_work` a lze je tudíž velmi jednoduše upravovat či rozšiřovat. To samé je u funkce `check_argument` která kontroluje validy argumentu a nachází se v ní tudíž všechny podmínky.


### Analýza instrukcí
Analýza instrukcí zdrojového kódu jazyka IPPcode22 probíhá v následujících krocích:

1) Podle argumentů scriptu čte řádek po řádku buď ze souboru/ů či ze STDIN, ignorujíc prázdné řádky a řádky jen s komentáři. Pokud řádek obsahuje více mezer mezi argumenty, přebytečné mezery jsou odstraněny.

2) Zkontroluje se jestli první neprázdný řádek obsahuje hlavičku `.IPPcode22`, pokud ne, script končí chybou 21

3) Poté přejde na víše zmiňovaný switch case, který kontroluje každý řádek pro instrukce a ukládá je do DOMDocument. Pokud bude načtena instrukce s nepodporovaným operačním kódem, tak skript končí chybou 22 či pokud bude nalezena lexikální, nebo syntaktická chyba, tak skript končí chybou 23.

4) Po projití celeho zdrojového kódu vytiskne DOMDocument na STDOUT

### Výsledná reprezentace
Po úspěšné lexikální a syntaktické kontrole dochází ke generování výsledné XML reprezentace programu psaného v jazyce IPPCode22. 