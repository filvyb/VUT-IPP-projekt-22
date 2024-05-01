<?php
    ini_set('display_errors', 'stderr');

    function check_var_validity(string $input){

    }

    function xml_add_instr(DOMDocument $dom_out, $dom_root, int $order, string $name){
        $dom_instr = $dom_out->createElement("instruction");
        $dom_instr->setAttribute("order", $order);
        $dom_instr->setAttribute("opcode", $name);
        $dom_root->appendChild($dom_instr);
        return $dom_instr;
    }

    function xml_add_instr_arg(DOMDocument $dom_out, $dom_root, int $arg_order, $types){
        $types = $types[$arg_order - 1];
        $value = $types[1];
        $type = $types[0];
        $tmpstr = "arg";
        $tmpstr .= $arg_order;
        $dom_arg = $dom_out->createElement($tmpstr, htmlentities($value, ENT_XML1));
        $dom_arg->setAttribute("type", $type);
        $dom_root->appendChild($dom_arg);
    }

    function check_argument(string $input, string $data_type): array{
        $sym_split = explode("@", $input, 2);
        if(in_array($data_type, ["var", "symb"])){
            if($data_type == "var"){
                if(in_array($sym_split[0] , ["GF", "LF", "TF"]) and preg_match("/^[_\-$&%*!?a-zA-Z][_\-$&%*!?a-zA-Z0-9]*$/", $sym_split[1])){
                    return ["var", $input];
                } else {
                    exit(23);
                }
            }
            if($data_type == "symb"){
                if(in_array($sym_split[0] , ["GF", "LF", "TF"]) and preg_match("/^[_\-$&%*!?a-zA-Z][_\-$&%*!?a-zA-Z0-9]*$/", $sym_split[1])){
                    return ["var", $input];
                } elseif (in_array($sym_split[0], ["int", "float", "bool", "string", "nil"])){
                    if ($sym_split[0] == "int"){
                        //$tmp = (string)(int)$sym_split[1];
                        if($sym_split[1] == (string)(int)$sym_split[1] or preg_match("/[0-9]+$/", $sym_split[1])){
                            return [$sym_split[0], $sym_split[1]];
                        } else{
                            exit(23);
                        }
                    } elseif($sym_split[0] == "float"){
                        //$tmp = strval((float)$sym_split[1]);
                        return [$sym_split[0], $sym_split[1]];
                        //if($sym_split[1] == $tmp){
                        //    return [$sym_split[0], $sym_split[1]];
                        //} else{
                        //    exit(23);
                        //}
                    } elseif($sym_split[0] == "bool"){
                        if(in_array($sym_split[1], ["true", "false"])){
                            return [$sym_split[0], $sym_split[1]];
                        } else{
                            exit(23);
                        }
                    } elseif($sym_split[0] == "string"){
                        if(str_ends_with($sym_split[1], "\\")){
                            exit(23);
                        }
                        return [$sym_split[0], $sym_split[1]];
                    } elseif($sym_split[0] == "nil"){
                        if($sym_split[1] != "nil"){
                            exit(23);
                        }
                        return ["nil", "nil"];
                    }
                } else {
                    exit(23);
                }
            }
        }elseif ($data_type == "label"){
            if(preg_match("/^[_\-$&%*!?a-zA-Z][_\-$&%*!?a-zA-Z0-9]*$/", $input)){
                return ["label", $sym_split[0]];
            } else{
                exit(23);
            }
        } elseif ("type") {
            if (in_array($sym_split[0], ["int", "float", "bool", "string"]) and count($sym_split) == 1) {
                return ["type", $sym_split[0]];
            } elseif ($sym_split[0] == "nil"  and count($sym_split) == 1){
                return ["nil", "nil"];
            } else {
                exit(23);
            }
        } else{
            exit(99);
        }
        return ["nil", "nil"];
    }

    function remove_multi_spaces(string $input): string{
        $input = trim($input);
        $output = "";
        for($i=0; $i<strlen($input); $i++){
            if($input[$i] == " " and $input[$i + 1] == " "){
                continue;
            }
            $output .= $input[$i];
        }
        return $output;
    }

    function too_many_args_error(array $line, int $limit){
        if(count($line)-1 != $limit){
            exit(23);
        }
    }

    function do_work($filepath){
        if ($filepath == !null) {
            $input_stream = fopen($filepath, "r");
            if (!$input_stream) {
                exit(11);
            }
            //$input_stream = fgets($input_stream);
        } else {
            $input_stream = STDIN;
        }

        $dom_out = new DOMDocument();
        $xml_file_out = 'out.xml';

        $dom_out->encoding = 'utf-8';
        $dom_out->xmlVersion = '1.0';
        $dom_out->formatOutput = true;
        $dom_root = 0;

        $hit_header = false;
        $order = 1;

        while ($line = fgets($input_stream)) {
            $comments_gone = explode("#", $line)[0];
            if ($comments_gone == "" or $comments_gone == "\n") {
                continue;
            }
            if (!$hit_header) {
                if (trim($comments_gone) == ".IPPcode22") {
                    $hit_header = true;
                    $dom_root = $dom_out->createElement("program");
                    $dom_root->setAttribute("language", "IPPcode22");
                    $dom_out->appendChild($dom_root);
                    continue;
                } else {
                    exit(21);
                }
            }
            //$comments_gone = str_replace("&", "&amp", $comments_gone);
            //$comments_gone = str_replace("<", "&lt", $comments_gone);
            //$comments_gone = str_replace(">", "&gt", $comments_gone);
            $comments_gone = remove_multi_spaces($comments_gone);
            //$comments_gone = htmlentities($comments_gone, ENT_XML1);
            $line_split = explode(" ", $comments_gone);
            $instruct = strtoupper($line_split[0]);

            switch ($instruct) {
                case "MOVE":
                    too_many_args_error($line_split, 2);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    break;
                case "CREATEFRAME":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "PUSHFRAME":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "POPFRAME":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "DEFVAR":
                    too_many_args_error($line_split, 1);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    break;
                case "CALL":
                    too_many_args_error($line_split, 1);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "label")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    break;
                case "RETURN":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "PUSHS":
                    too_many_args_error($line_split, 1);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    break;
                case "POPS":
                    too_many_args_error($line_split, 1);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    break;
                case "CLEARS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "ADD":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "SUB":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "MUL":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "DIV":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "IDIV":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "ADDS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "SUBS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "MULS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "DIVS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "IDIVS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "LT":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "GT":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "EQ":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "LTS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "GTS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "EQS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "AND":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "OR":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "NOT":
                    too_many_args_error($line_split, 2);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    break;
                case "ANDS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "ORS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "NOTS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "INT2FLOAT":
                    too_many_args_error($line_split, 2);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    break;
                case "FLOAT2INT":
                    too_many_args_error($line_split, 2);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    break;
                case "INT2CHAR":
                    too_many_args_error($line_split, 2);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    break;
                case "STRI2INT":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "INT2FLOATS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "FLOAT2INTS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "INT2CHARS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "STRI2INTS":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "READ":
                    too_many_args_error($line_split, 2);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "type")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    break;
                case "WRITE":
                    too_many_args_error($line_split, 1);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    break;
                case "CONCAT":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "STRLEN":
                    too_many_args_error($line_split, 2);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    break;
                case "GETCHAR":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "SETCHAR":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "TYPE":
                    too_many_args_error($line_split, 2);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "var"), check_argument($line_split[2], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    break;
                case "LABEL":
                    too_many_args_error($line_split, 1);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "label")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    break;
                case "JUMP":
                    too_many_args_error($line_split, 1);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "label")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    break;
                case "JUMPIFEQ":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "label"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "JUMPIFNEQ":
                    too_many_args_error($line_split, 3);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "label"), check_argument($line_split[2], "symb"), check_argument($line_split[3], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 2, $types);
                    xml_add_instr_arg($dom_out, $dom_instr, 3, $types);
                    break;
                case "JUMPIFEQS":
                    too_many_args_error($line_split, 1);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "label")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    break;
                case "JUMPIFNEQS":
                    too_many_args_error($line_split, 1);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "label")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    break;
                case "EXIT":
                    too_many_args_error($line_split, 1);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    break;
                case "BREAK":
                    too_many_args_error($line_split, 0);
                    xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    break;
                case "DPRINT":
                    too_many_args_error($line_split, 1);
                    $dom_instr = xml_add_instr($dom_out, $dom_root, $order, $instruct);
                    $order++;
                    $types = [check_argument($line_split[1], "symb")];
                    xml_add_instr_arg($dom_out, $dom_instr, 1, $types);
                    break;
                default:
                    exit(22);

            }
            //printf("%s %s \n",$order, $comments_gone);
        }
        print($dom_out->saveXML());
        //$dom_out->save($xml_file_out);
        //print("Done ".$filepath."\n");
    }

    $input_path = "";

    if ($argc > 1){
        if ($argv[1] == "--help" and $argc == 2) {
            print("Usage: ");
            print("To use this script you must either pipe your IPP22code into STDIN without any additional arguments or use the argument --source=input with input being either a file containing IPP22code or a directory containing multiple files with IPP22code. The results are outputted to STDOUT.");
            exit(0);
        } elseif (in_array("--help", $argv) and $argc > 2) {
            exit(10);
        } elseif (explode("=", $argv[1])[0] == "--source") {
            $input_path = trim(explode("=", $argv[1])[1], '"');
        } else {
            exit(10);
        }
    }

    if(is_dir($input_path)){
        $dir = new RecursiveDirectoryIterator($input_path);
        $dir->setFlags(FilesystemIterator::SKIP_DOTS);
        $dir_iter = new RecursiveIteratorIterator($dir);

        foreach ($dir_iter as $the_file){
            do_work($the_file->getPathname());
        }
    } elseif(is_file($input_path)){
        do_work($input_path);
    } elseif ($argc == 1){
        do_work(null);
    } else{
        exit(11);
    }

?>