<?php

ini_set('display_errors', 'stderr');

// Hodnoty pro zadaný výchozí stav scriptu

$jexampath = "/pub/courses/ipp/jexamxml/";
$directory = getcwd();
$recursive = false;
$parser_path = "parse.php";
$interpreter_path = "interpret.py";
$which_test = "both";
$noclean = false;
$testfile_ext = "";
$testfile_regex = "";


if ($argv[1] == "--help" and $argc == 2){
    print("Help: ");
    print("--directory=path testy bude hledat v zadaném adresáři (chybí-li tento parametr, skript prochází aktuální adresář)\n");
    print("--recursive testy bude hledat nejen v zadaném adresáři, ale i rekurzivně ve všech jeho podadresářích\n");
    print("--parse-script=file soubor se skriptem v PHP 8.1 pro analýzu zdrojového kódu v IPP-code22 (chybí-li tento parametr, implicitní hodnotou je parse.php uložený v aktuálním adresáři\n");
    print("--int-script=file soubor se skriptem v Python 3.8 pro interpret XML reprezentace kódu v IPPcode22 (chybí-li tento parametr, implicitní hodnotou je interpret.py uložený v aktuálním adresáři)\n");
    print("--parse-only bude testován pouze skript pro analýzu zdrojového kódu v IPPcode22 (tento parametr se nesmí kombinovat s parametry --int-only a --int-script), výstup s referenčním výstupem (soubor s příponou out) porovnávejte nástrojem A7Soft JExamXML\n");
    print("--int-only bude testován pouze skript pro interpret XML reprezentace kódu v IPP-code22 (tento parametr se nesmí kombinovat s parametry --parse-only, --parse-script a --jexampath). Vstupní program reprezentován pomocí XML bude v souboru s příponou src.\n");
    print("--jexampath=path cesta k adresáři obsahující soubor jexamxml.jar s JAR balíčkem s nástrojem A7Soft JExamXML a soubor s konfigurací jménem options. Je-li parametr vynechán, uvažuje se implicitní umístění /pub/courses/ipp/jexamxml/.\n");
    print("--noclean během činnosti test.php nebudou mazány pomocné soubory s mezivýsledky, tj.skript ponechá soubory, které vznikají při práci testovaných skriptů\n");
    print("--testlist=file sloužící pro explicitní zadání seznamu adresářů (zadaných relativními či absolutními cestami) a případně i souborů s testy (zadává se soubor s příponou .src) formou externího souboru file místo načtení testů z aktuálního adresáře (nelze kombinovat s parametrem --directory)\n");
    print("--match=regexp pro výběr testů, jejichž jméno bez přípony (ne cesta) odpovídá zadanému regulárnímu výrazu regexp\n");
    exit(0);
} elseif (in_array("--help", $argv) and $argc > 2){
    exit(10);
} elseif ($argc == 1){
    exit(10);
} else{
    $passed_arguments = [];
    for ($i=1;$i<$argc;$i++){
        $sep_arg = explode("=", $argv[$i], 2);
        $passed_arguments[] = $sep_arg[0];
        switch ($sep_arg[0]) {
            case "--directory":
                if(in_array("--testlist",$passed_arguments)){
                    exit(10);
                }
                if(!is_dir($sep_arg[1])){
                    exit(41);
                }
                $directory = $sep_arg[1];
                break;
            case "--recursive":
                $recursive = true;
                break;
            case "--parse-script":
                if(!is_file($sep_arg[1])){
                    exit(41);
                }
                $parser_path = $sep_arg[1];
                break;
            case "--int-script":
                if(!is_file($sep_arg[1])){
                    exit(41);
                }
                $interpreter_path = $sep_arg[1];
                break;
            case "--parse-only":
                if(!empty(array_intersect(["--int-script", "--int-only"],$passed_arguments))){
                    exit(10);
                }
                $which_test = "parse";
                break;
            case "--int-only":
                if(!empty(array_intersect(["--parse-script", "--parse-only", "--jexampath"],$passed_arguments))){
                    exit(10);
                }
                $which_test = "int";
                break;
            case "--jexampath":
                if(!is_dir($sep_arg[1])){
                    exit(41);
                }
                $jexampath = $sep_arg[1];
                break;
            case "--noclean":
                $noclean = true;
                break;
            case "--testlist":
                if(in_array("--directory",$passed_arguments)){
                    exit(10);
                }
                if(!is_file($sep_arg[1])){
                    exit(41);
                }
                $testfile_ext = $sep_arg[1];
                break;
            case "--match":
                $testfile_regex = $sep_arg[1];
                break;

            default:
                exit(10);

        }
    }
}

if($testfile_ext != ""){
    $directory = $testfile_ext;
}

find_tests_and_perform($directory, $recursive, $which_test);


// Hlavní funkce skriptu
function find_tests_and_perform($directory_path, $recurse, $which){
    // Složí pro extrakci cest při použití rozšíření files
    $lines = [];
    if (is_file($directory_path)){
        $lines = file($directory_path, FILE_IGNORE_NEW_LINES);
    } else {
        $lines[] = $directory_path;
    }

    $dir_iter = [];

    // Iteruje přes zadanou/né cesty a ukládá cesty ke všem souborům
    foreach ($lines as $dir_item) {
        if ($recurse == false) {
            $dir_iter_tmp = new FilesystemIterator($dir_item);
            $dir_iter_tmp->setFlags(FilesystemIterator::SKIP_DOTS);
            foreach ($dir_iter_tmp as $testfiles){
                $dir_iter[] = $testfiles->getPathname();
            }
        } elseif (is_file($dir_item)) {
            $dir_iter[] = $dir_item;
        } else {
            $dir = new RecursiveDirectoryIterator($dir_item);
            $dir->setFlags(FilesystemIterator::SKIP_DOTS);
            $dir_iter_tmp = new RecursiveIteratorIterator($dir);
            foreach ($dir_iter_tmp as $testfiles){
                $dir_iter[] = $testfiles->getPathname();
            }
        }
    }


    // Odstranuje ze seznamu cesty cesty co neodpovídají regex při použití rozšíření files
    global $testfile_regex;
    $tmp_array = [];
    if($testfile_regex != ""){
        foreach ($dir_iter as $item){
            if(preg_match($testfile_regex)){
                $tmp_array[] = $item;
            }
        }
        $dir_iter = $tmp_array;
    }


    // Počitatelé uskutečněných a úspěšných testů
    $test_counter = 0;
    $suc_tests = 0;

    // DOMDocument je použit na stabvu finálního HTML reportu
    $doc = new DOMDocument("1.0", "utf-8");
    $doc->formatOutput = true;
    $html = $doc->appendChild($doc->createElement('html'));
    $head = $html->appendChild($doc->createElement('head'));
    $head->appendChild($doc->createElement('title', "IPPcode22 Výsledky testů"));
    $head->appendChild($doc->createElement('style', "table, th, td {border:2px solid black;}"));
    $body = $html->appendChild($doc->createElement('body'));
    $body->appendChild($doc->createElement('h1', "IPPcode22 Výsledky testů"));
    $result_sum = $body->appendChild($doc->createElement('h2'));
    $result_sum_list = $body->appendChild($doc->createElement('ul'));
    $body->appendChild($doc->createElement('h2', "Konfigurace testů"));
    $config_sum = $body->appendChild($doc->createElement('ul'));
    $config_sum->appendChild($doc->createElement('li', "Adresář: ".$directory_path));
    if($recurse == true){
        $config_sum->appendChild($doc->createElement('li', "Rekurzivní prohledávání"));
    }
    if($which == "int"){
        $config_sum->appendChild($doc->createElement('li', "Pouze interpret"));
    } elseif ($which == "parse"){
        $config_sum->appendChild($doc->createElement('li', "Pouze parse"));
    } else {
        $config_sum->appendChild($doc->createElement('li', "Interpret i parse"));
    }
    $body->appendChild($doc->createElement('h2', "Výsledky jednotlivých testů"));
    $result_table = $body->appendChild($doc->createElement('table'));
    $attr = $doc->createAttribute("style");
    $attr->value = "width: 80%";
    $result_table->appendChild($attr);
    $column_names = $result_table->appendChild($doc->createElement('tr'));
    $column_names->appendChild($doc->createElement('th', "Jmeno testu"));
    $column_names->appendChild($doc->createElement('th', "Výsledek"));
    $column_names->appendChild($doc->createElement('th', "Informace"));
    $column_names->appendChild($doc->createElement('th', "Ref exit code"));
    $column_names->appendChild($doc->createElement('th', "Vas exit code"));

    // Vytvoří složku pro dočastné soubory
    if(!is_dir("tmp")) {
        mkdir("tmp");
    }

    // Iteruje přes vybrané cesty hledajíc soubory s příponou .src a volá na ně funkcy perform_test
    foreach ($dir_iter as $file){
        //$file = $the_file->getPathname();
        $tmp = explode(".", $file);
        if(is_file($file) and end($tmp) == "src") {
            $test_counter++;
            if(perform_test($file, $which, $doc, $result_table) == true){
                $suc_tests++;
            }
        }
    }

    // Pokud není vybráno noclean smaže dočastné soubory
    global $noclean;
    if(!$noclean){
        $files = glob("tmp/*");

        foreach($files as $file){
            if(is_file($file)){
                unlink($file);
            }
        }
        rmdir("tmp");
    }

    $result_sum->nodeValue = strval((int)(($suc_tests / $test_counter) * 100))."%";
    $result_sum_list->appendChild($doc->createElement('li', "Úspěšných: ".strval($suc_tests)));
    $result_sum_list->appendChild($doc->createElement('li', "Neúspěšných: ".strval($test_counter - $suc_tests)));
    $result_sum_list->appendChild($doc->createElement('li', "Celkem: ".strval($test_counter)));

    print($doc->saveHTML());
    #$doc->saveHTMLFile("out_test.html");
}

// Tato funkce vždy uskuteční jeden test a zapíše jeho výsledky do finálního HTML
// Vrací boolean jestli test uspěl
function perform_test($test_source, $which, $doc, $table): bool {
    $test_folder = explode("/", $test_source, -1);
    $test_folder = implode("/", $test_folder)."/";
    $test_name = explode("/", $test_source);
    $test_name = explode(".", end($test_name))[0];
    $tmp_file = "tmp/".$test_name.".tmp";
    $exit_code = 0;

    // Vytváří chybející soubory .in a .out
    if(!is_file($test_folder.$test_name.".out")){
        exec("touch ".$test_folder.$test_name.".out");
    }
    if(!is_file($test_folder.$test_name.".in")){
        exec("touch ".$test_folder.$test_name.".in");
    }

    // Podle zadaného argumentu vybere patřičný příkaz
    if($which == "int"){
        global $interpreter_path;
        $command = "python3.8 ".$interpreter_path." --source=".$test_source." --input=".$test_folder.$test_name.".in > ".$tmp_file." 2> ".$tmp_file."_err";
    } elseif ($which == "parse"){
        global $parser_path;
        $command = "php8.1 ".$parser_path." < ".$test_source." > ".$tmp_file." 2> ".$tmp_file."_err";
    } else {
        global $parser_path;
        global $interpreter_path;
        $command = "php8.1 ".$parser_path." < ".$test_source." | python3.8 ".$interpreter_path." --input=".$test_folder.$test_name.".in > ".$tmp_file." 2> ".$tmp_file."_err";
    }

    exec($command, result_code: $exit_code);

    $results = compare_results($test_folder, $test_name, $tmp_file, $exit_code, $which);

    $is_good = false;

    $columns = $table->appendChild($doc->createElement('tr'));
    $columns->appendChild($doc->createElement('td', $test_source));

    // Podle exit kódu testu, referenčního exit kódu a exit kódu porovnání je určeno jestli test uspěl
    if ($results[0] == $exit_code and ($results[1] == 0 or $results[1] != 1)) {
        $is_good = true;
        $columns->appendChild($doc->createElement('td', "Splnil"));
        $columns->appendChild($doc->createElement('td', "Výsledek je identický"));
    } else {
        $columns->appendChild($doc->createElement('td', "Nesplnil"));
        clearstatcache();
        $xd = filesize("tmp/".$test_name.".delta");
        if(!filesize("tmp/" . $test_name . ".delta")) {
            $tmpfilecont = file_get_contents($tmp_file);
            $tmpfilecont_err = file_get_contents($tmp_file."_err");
            if(filesize($tmp_file)){
                $columns->appendChild($doc->createElement('td', $tmpfilecont));
            } elseif(filesize($tmp_file . "_err")){
                $columns->appendChild($doc->createElement('td', $tmpfilecont_err));
            } else{
                $columns->appendChild($doc->createElement('td', "Delta či error chybí"));
            }
        } else {
            $cont = file_get_contents("tmp/".$test_name.".delta");
            $cont = htmlspecialchars($cont);
            $columns->appendChild($doc->createElement('td', $cont));
        }
    }
    $columns->appendChild($doc->createElement('td', strval($results[0])));
    $columns->appendChild($doc->createElement('td', strval($exit_code)));

    return $is_good;
}

// Funkce porovná výsledky testu s očekávanými výsledky
// Vrací array s referenčním exit kódem a s exit kódem porovnání
function compare_results($test_folder, $test_name, $result, $exit_code, $which){
    if($which == "parse"){
        global $jexampath;
        $command = "java -jar ".$jexampath."jexamxml.jar ".$result." ".$test_folder.$test_name.".out tmp/".$test_name.".delta ".$jexampath."options";
    } else {
        $command = "diff --strip-trailing-cr ".$result." ".$test_folder.$test_name.".out > tmp/".$test_name.".delta";
    }

    if(!is_file($test_folder.$test_name.".rc")){
        exec("echo \"0\" > ".$test_folder.$test_name.".rc");
    }
    $ref_exit = intval(file_get_contents($test_folder.$test_name.".rc"));

    $test_return = 0;

    // Pokud se test ukončí se správným chybovím kódem, tak se porovnání stdout nedělá a tudíž je považováno za úspěšné
    if($exit_code == $ref_exit and $exit_code != 0){
        return [$ref_exit, $test_return];
    }

    exec($command, result_code: $test_return);

    return [$ref_exit, $test_return];
}

?>