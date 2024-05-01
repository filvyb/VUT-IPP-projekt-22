Implementační dokumentace k 2. úloze do IPP 2021/2022
Jméno a příjmení: Filip Vybíhal
Login: xvybih06

## Interpret jazyka IPPCode22 (interpret.py)

Interpret jazyka IPPCode22 přijímá na standardním vstupu (nebo ze souboru pomocí parametru `--source`) XML soubor reprezentující jazyk IPPCode22.
Interpret provádí syntaktickou a lexikální analýzu obsahu a následnou interpretaci programu.

### Načítání dat a reprezentace instrukcí

Načítaní instrukcí probíhá v hlavním těle scriptu, ze stringu do, kterého je načten XML soubor reprezentující jazyk IPPCode22. Instrukce se ukládají do seznamu objektů `Instruction`, který obsahuje jejich opcode, order a seřazený seznam argumentů. Argumenty jsou reprezentovány objekty `Variable`, `Symbol`, `Type` a `Label` do kterých se uloží hodnota argumentu.

Jazyk IPPCode22 pracuje s typy dat int(, float), bool, string a nil. Data se při načítání (při tvorbě objektu `Symbol` či u exekuce instrukce READ) převádí do nativních typů Pythonu, s výjimkou nil, která by logicky odpovídala v Pythonu None, ovšem None je určenou pro neinicializovanou proměnou a proto pro reprezentaci nil používám vlastní třídu `nil`.

### Interpretace

Interpretace probíhá ve funkci `interpret`, která bere seznamu objektů `Instruction` jako argument. Nejdříve inicializuje rámce tvorbou korespondujících objektů, poté seřadí instrukce dle hodnoty order, projde seznam instrukcí pro zjištění pozic návěští a poté již interpretuje. Samotná interpretace probíhá iterací na seznam instrukcí pomocí while loop, ve kterém se nachází obrovský blok if ... elif ... elif, kde každá podmínka odpovídá jedné instrukci. V podmínce se potom provedou korespondují akce.

Rámce jsou řešeny pomocí tříd `Frame`, `GlobalFrame`, `TemporaryFrame` a `LocalFrames`. Třída `Frame` reprezentuje 1 obecný rámec a obsahuje metody pro vkládání a získávání dat, které zároveň ošetřují chyby při práci s rámci. Pro samotné ukládání dat v rámci je použit slovník, kde klíčem je jméno proměnné. Třída `Frame` tudíž používá proxy návrhový vzor. Třídy `GlobalFrame`, `TemporaryFrame` dědí třídu `Frame` a obsahují metody specifické rámcům co reprezentují. Třída `LocalFrames` nedědí, ovšem ve svém zásobníku rámců využívá třídu `Frame`.

### Rozšíření

* FLOAT - Podpora datového typu float. Podpora tohoto typu v instrukcích + nové instrukce pro práci s tímto typem (INT2FLOAT, DIV, ...)
* NVI - aplikováno objektově orientované programování


## Testovací skript (test.php)

Skript za pomocí testovacích dat automatické testy skriptů parse.php a interpret.py.

Skript při svém spuštění a načtení konfigurace z příkazové řádky provede vyhledání testů a jejich následné spuštění.

Vyhledávání testů je možné provádět rekurzivně za pomocí parametru `--recursive`. Vyhledávání se odvíjí od zadaného adresáře v parametru `--directory=`. Pokud není zadán, bere se aktuální adresář. Dočasné soubory se mažou na konci scriptu. Zakázat jejich mazání lze parametrem `--noclean`.


### Provádění testů

Prvně se provede vyhodnocení, ve kterém režimu se má testování provést. Jsou k dispozici následující režimy:

* `both` - Výchozí režim, kdy se provádí testování obou skriptů najednou.
  * Testovací skript vezme zdrojový kód, ten předá skriptu `parse.php`, výstup tohoto skriptu je předán skriptu `interpret.py`.
* `interpret-only` - Testování probíhá pouze nad skriptem `interpret.py`. Spouští se pomocí parametru `--int-only`.
* `parser-only` - Testování probíhá pouze nad skriptem `parse.php`. Spouští se pomocí parametru `--parse-only`.

### Generování HTML reportu

Výstup skriptu `test.php` je HTML stránka vypsaná na standardní výstup. Obsahuje souhrnné informace o konfiguraci testovacího skriptu, souhrnný výsledek testů v podobně počtu úspěšných/neúspěšných testů a výstupy jednotlivých testů.

### Rozšíření

* FILES - Podporujte parametr `--testlist=` sloužící pro explicitní zadání seznamu adresářů a případně i souborů s testy. Nelze kombinovat s parametrem `--directory=`