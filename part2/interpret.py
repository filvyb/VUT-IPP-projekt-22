import sys
import argparse
import xml.etree.ElementTree as xmltree


# Nativní typ None je využit pro poznání nedefinované var a tak ho zastupuje tato třída pro reprezentaci nil
class nil:
    def __init__(self):
        self.val = "nil"

    def __str__(self):
        return self.val


# Třída reprezentující argument, sama o sobě není využita, pouze složí jako rodič
class Argument:
    def __init__(self, arg_type):
        self.arg_type = arg_type

    def get_type(self):
        return self.arg_type


# Třída reprezentující argument typu var
class Variable(Argument):
    def __init__(self, arg_type, value):
        super().__init__(arg_type)
        tmp = value.split("@", 1)
        self.frame = tmp[0]
        self.value = tmp[1]

    def __str__(self):
        return self.arg_type + ": " + self.frame + "@" + self.value + " "

    def get_frame(self):
        return self.frame

    def get_value(self):
        return self.value


# Třída reprezentující argument symb
class Symbol(Argument):
    def __init__(self, arg_type, value):
        super().__init__(arg_type)
        if arg_type == "string":
            if value is None:
                self.value = ""
            else:
                # I HATE REGEX I HATE REGEX https://knowyourmeme.com/memes/i-hate-the-antichrist
                # Přepisuje ASCII kódy na znaky
                ascii_codes = []
                for i in range(0, 1000):
                    ascii_codes.append('\\' + str(i).zfill(3))
                for i in ascii_codes:
                    ind = value.find(i)
                    if ind != -1:
                        ascii_char = value[ind:ind + 4]
                        value = value.replace(ascii_char, chr(int(ascii_char[1:])))
                self.value = value
        elif arg_type == "int":
            self.value = int(value)
        elif arg_type == "float":
            try:
                self.value = float(value)
            except:
                self.value = float.fromhex(value)
        elif arg_type == "bool":
            if value == "true":
                value = True
            else:
                value = False
            self.value = value
        elif arg_type == "nil":
            self.value = nil()
        else:
            self.value = value

    def __str__(self):
        return self.arg_type + ": " + str(self.value) + " "

    def get_value(self):
        return self.value


# Třída reprezentující argument type
class Type(Argument):
    def __init__(self, arg_type, value):
        super().__init__(arg_type)
        self.value = value

    def __str__(self):
        return self.arg_type + ": " + self.value + " "

    def get_value(self):
        return self.value


# Třída reprezentující argument Label
class Label(Argument):
    def __init__(self, arg_type, value):
        super().__init__(arg_type)
        self.value = value

    def __str__(self):
        return self.arg_type + ": " + self.value + " "

    def get_value(self):
        return self.value


# Třída reprezentující instrukci IPPcode22, argumenty ukládá v listu dětí objektu Argument
class Instruction:
    def __init__(self, name, order):
        self.name = name.upper()
        self.order = int(order)
        self.args = []

    def __str__(self):
        tmps = ""
        for i in self.args:
            tmps = tmps + str(i)
        return str(self.order) + " " + self.name + " " + tmps

    def add_argument(self, arg_type, value):
        if arg_type == "var":
            self.args.append(Variable(arg_type, value))
        elif arg_type == "type":
            self.args.append(Type(arg_type, value))
        elif arg_type == "label":
            self.args.append(Label(arg_type, value))
        else:
            self.args.append(Symbol(arg_type, value))

    def get_name(self):
        return self.name

    def get_order(self):
        return self.order

    def get_arg_list(self):
        return self.args


# Obecná třída pro rámec
class Frame:
    def __init__(self):
        self.Frame = {}

    def __str__(self):
        return str(self.Frame)

    def insert(self, address):
        self.Frame[address] = None

    def update(self, address, value):
        if address not in self.Frame.keys():
            exit(54)
        self.Frame[address] = value

    def get(self, address):
        if address not in self.Frame.keys():
            exit(54)
        return self.Frame.get(address)

    def expose(self):
        return self.Frame

    def var_exists(self, address):
        if address in self.Frame:
            return True
        else:
            return False


# Třída reprezentující globální rámec
class GlobalFrame(Frame):
    def __init__(self):
        super().__init__()


# Třída reprezentující dočastný rámec
class TemporaryFrame(Frame):
    def __init__(self):
        super().__init__()
        self.Frame = None

    def make_new(self, new=None):
        if new is None:
            new = {}
        self.Frame = new

    def empty(self):
        self.Frame = None

    def exists(self):
        if self.Frame is not None:
            return True
        else:
            return False

# Třída reprezentující zásobník lokálních rámců, do zásobníku ukládá objekty Frame
class LocalFrames:
    def __init__(self):
        self.LF = []

    def __str__(self):
        return str(self.LF)

    def new_level(self):
        self.LF.append(Frame())

    def remove_level(self):
        if len(self.LF) == 0:
            exit(55)
        pop = self.LF.pop()
        if pop is None:
            exit(55)
        return pop

    def get_top(self):
        ret = ""
        try:
            ret = self.LF[-1]
        except:
            exit(55)
        return ret


# Třída reprezentující "rámec" pro ukládání pozic návěští
class Labels(Frame):
    def __init__(self):
        super().__init__()

    def new(self, address, value):
        if address in self.Frame.keys():
            exit(52)
        self.Frame[address] = value

    def get(self, address):
        ret = self.Frame.get(address)
        if ret is None:
            exit(52)
        return ret

    def update(self, address, value):
        exit(52)


# Funkce vrací rámec argumentu
def figure_out_frame(GF, LF, TF, frame):
    ret_fr = ""
    if frame == "GF":
        ret_fr = GF
    elif frame == "LF":
        ret_fr = LF.get_top()
    else:
        if not TF.exists():
            exit(55)
        ret_fr = TF
    return ret_fr


# Funkce vrací typ hodnoty v podobě stringu
def figure_out_type(value):
    if type(value) is int:
        return "int"
    if type(value) is str:
        return "string"
    if type(value) is bool:
        return "bool"
    if type(value) is float:
        return "float"
    if type(value) is nil:
        return "nil"
    if value is None:
        return ""


# Funkce vrací hodnotu argument popřípadě hodnotu v rámcích na kterou argument ukazuje
def get_arg_value(GF, LF, TF, arg):
    if arg.get_type() == "var":
        frame = figure_out_frame(GF, LF, TF, arg.get_frame())
        if frame.Frame is None:
            exit(55)
        ret = frame.get(arg.get_value())
        if ret is None:
            exit(56)
        return ret
    else:
        return arg.get_value()


# Hlavní funkce scriptu iterující a interpretujíci seznam objektů Instruction
def interpret(instruction_list):
    GF = GlobalFrame()
    LF = LocalFrames()
    TF = TemporaryFrame()
    labels = Labels()
    call_stack = []
    data_stack = []

    # Seřadí seznam instrukcí podle pořadí zadaného v XML
    instruction_list = sorted(instruction_list, key=lambda x: x.get_order())

    # Přepíše čísla pořadí instrukcí aby pořadí odpovídalo 1, 2, 3, 4...
    for i in range(0, len(instruction_list)):
        instruction_list[i].order = i + 1

    # Projde seznam instrukcí poprvé a najde pozice návěští
    for i in instruction_list:
        if i.get_name() == "LABEL":
            arg1 = i.get_arg_list()[0]

            labels.new(arg1.get_value(), i.get_order() - 1)

    # print(labels)

    # Iteruje přes seznam objektů Instruction a podle jména instrukce určuje co udělat
    # Je využit while loop a číselná indexace listu místo Pythonovského for in, protože dovoluje měnit i a tudíž umožnuje skákání instrukcemi jump, call apod. 
    i = 0
    while i < len(instruction_list):
        instr = instruction_list[i]
        # print(instr)
        # print(GF, LF.get_top(), TF)

        if instr.get_name() == "MOVE":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]

            copied_val = get_arg_value(GF, LF, TF, arg2)

            frame1 = figure_out_frame(GF, LF, TF, arg1.get_frame())
            frame1.update(arg1.get_value(), copied_val)

        elif instr.get_name() == "CREATEFRAME":
            TF.make_new()

        elif instr.get_name() == "PUSHFRAME":
            tmp_dic = TF.expose()
            if tmp_dic is None:
                exit(55)
            LF.new_level()
            for x in tmp_dic:
                LF.get_top().insert(x)
                LF.get_top().update(x, tmp_dic[x])
            TF.empty()

        elif instr.get_name() == "POPFRAME":
            exp = LF.remove_level().expose()
            TF.make_new(exp)

        elif instr.get_name() == "DEFVAR":
            address = instr.get_arg_list()[0].get_value()
            frame = instr.get_arg_list()[0].get_frame()
            frame = figure_out_frame(GF, LF, TF, frame)
            if frame.Frame is None:
                exit(55)
            if frame.var_exists(address) is True:
                exit(52)
            frame.insert(address)
        elif instr.get_name() == "CALL":
            arg1 = instr.get_arg_list()[0]

            call_stack.append(i + 1)
            i = labels.get(arg1.get_value())
            continue

        elif instr.get_name() == "RETURN":
            try:
                i = call_stack.pop()
            except:
                exit(56)
            continue

        elif instr.get_name() == "PUSHS":
            arg = instr.get_arg_list()[0]
            val = get_arg_value(GF, LF, TF, arg)
            data_stack.append(val)

        elif instr.get_name() == "POPS":
            if len(data_stack) == 0:
                exit(56)

            arg = instr.get_arg_list()[0]
            frame = figure_out_frame(GF, LF, TF, arg.get_frame())
            ret = data_stack.pop()
            frame.update(arg.get_value(), ret)

        elif instr.get_name() == "ADD":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)

            val2_t = figure_out_type(val2)
            val3_t = figure_out_type(val3)

            if (val2_t == "int" and val3_t == "int") or (val2_t == "float" and val3_t == "float"):
                frame = figure_out_frame(GF, LF, TF, arg1.get_frame())
                tmp = get_arg_value(GF, LF, TF, arg2) + get_arg_value(GF, LF, TF, arg3)
                frame.update(arg1.get_value(), tmp)
            else:
                exit(53)

        elif instr.get_name() == "SUB":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)

            val2_t = figure_out_type(val2)
            val3_t = figure_out_type(val3)

            if (val2_t == "int" and val3_t == "int") or (val2_t == "float" and val3_t == "float"):
                frame = figure_out_frame(GF, LF, TF, arg1.get_frame())
                tmp = get_arg_value(GF, LF, TF, arg2) - get_arg_value(GF, LF, TF, arg3)
                frame.update(arg1.get_value(), tmp)
            else:
                exit(53)

        elif instr.get_name() == "MUL":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)

            val2_t = figure_out_type(val2)
            val3_t = figure_out_type(val3)

            if (val2_t == "int" and val3_t == "int") or (val2_t == "float" and val3_t == "float"):
                frame = figure_out_frame(GF, LF, TF, arg1.get_frame())
                tmp = get_arg_value(GF, LF, TF, arg2) * get_arg_value(GF, LF, TF, arg3)
                frame.update(arg1.get_value(), tmp)
            else:
                exit(53)

        elif instr.get_name() == "DIV":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)

            val2_t = figure_out_type(val2)
            val3_t = figure_out_type(val3)

            if val2_t == "float" and val3_t == "float":
                frame = figure_out_frame(GF, LF, TF, arg1.get_frame())
                if get_arg_value(GF, LF, TF, arg3) == 0:
                    exit(57)
                tmp = get_arg_value(GF, LF, TF, arg2) / get_arg_value(GF, LF, TF, arg3)
                frame.update(arg1.get_value(), float(tmp))
            else:
                exit(53)

        elif instr.get_name() == "IDIV":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)

            val2_t = figure_out_type(val2)
            val3_t = figure_out_type(val3)

            if val2_t == "int" and val3_t == "int":
                frame = figure_out_frame(GF, LF, TF, arg1.get_frame())
                if get_arg_value(GF, LF, TF, arg3) == 0:
                    exit(57)
                tmp = get_arg_value(GF, LF, TF, arg2) / get_arg_value(GF, LF, TF, arg3)
                frame.update(arg1.get_value(), int(tmp))
            else:
                exit(53)

        elif instr.get_name() == "LT":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            frame = figure_out_frame(GF, LF, TF, arg1.get_frame())

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)

            val2_t = figure_out_type(val2)
            val3_t = figure_out_type(val3)

            if (val2_t == val3_t) and val2_t != "nil":
                if val2_t == "int" or val2_t == "float" or val2_t == "bool":
                    if val2 < val3:
                        frame.update(arg1.get_value(), True)
                    else:
                        frame.update(arg1.get_value(), False)
                if val2_t == "string":
                    tmp = [val2.lower(), val3.lower()]
                    tmp2 = sorted(tmp)
                    if tmp == tmp2 and val2.lower() != val3.lower():
                        frame.update(arg1.get_value(), True)
                    else:
                        frame.update(arg1.get_value(), False)
            else:
                exit(53)

        elif instr.get_name() == "GT":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            frame = figure_out_frame(GF, LF, TF, arg1.get_frame())

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)

            val2_t = figure_out_type(val2)
            val3_t = figure_out_type(val3)

            if (val2_t == val3_t) and val2_t != "nil":
                if val2_t == "int" or val2_t == "float" or val2_t == "bool":
                    if val2 > val3:
                        frame.update(arg1.get_value(), True)
                    else:
                        frame.update(arg1.get_value(), False)
                if val2_t == "string":
                    tmp = [val2.lower(), val3.lower()]
                    tmp2 = sorted(tmp)
                    if tmp == tmp2:
                        frame.update(arg1.get_value(), False)
                    else:
                        frame.update(arg1.get_value(), True)
            else:
                exit(53)

        elif instr.get_name() == "EQ":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            frame = figure_out_frame(GF, LF, TF, arg1.get_frame())

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)

            val2_t = figure_out_type(val2)
            val3_t = figure_out_type(val3)

            if (val2_t == val3_t) or val2_t == "nil" or val3_t == "nil":
                if str(val2) == str(val3):
                    frame.update(arg1.get_value(), True)
                else:
                    frame.update(arg1.get_value(), False)
            else:
                exit(53)

        elif instr.get_name() == "AND":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)
            if figure_out_type(val2) == "bool" and figure_out_type(val3) == "bool":
                frame = figure_out_frame(GF, LF, TF, arg1.get_frame())
                frame.update(arg1.get_value(), (val2 and val3))
            else:
                exit(53)

        elif instr.get_name() == "OR":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)
            if figure_out_type(val2) == "bool" and figure_out_type(val3) == "bool":
                frame = figure_out_frame(GF, LF, TF, arg1.get_frame())
                frame.update(arg1.get_value(), (val2 or val3))
            else:
                exit(53)

        elif instr.get_name() == "NOT":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]

            val2 = get_arg_value(GF, LF, TF, arg2)
            if figure_out_type(val2) == "bool":
                frame = figure_out_frame(GF, LF, TF, arg1.get_frame())
                frame.update(arg1.get_value(), not val2)
            else:
                exit(53)

        elif instr.get_name() == "INT2FLOAT":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]

            frame = figure_out_frame(GF, LF, TF, arg1.get_frame())

            val2 = get_arg_value(GF, LF, TF, arg2)

            if figure_out_type(val2) == "int":
                try:
                    out = float(val2)
                except:
                    exit(58)
            else:
                exit(53)

            frame.update(arg1.get_value(), out)

        elif instr.get_name() == "FLOAT2INT":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]

            frame = figure_out_frame(GF, LF, TF, arg1.get_frame())

            val2 = get_arg_value(GF, LF, TF, arg2)

            if figure_out_type(val2) == "float":
                try:
                    out = int(val2)
                except:
                    exit(58)
            else:
                exit(53)

            frame.update(arg1.get_value(), out)

        elif instr.get_name() == "INT2CHAR":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]

            frame = figure_out_frame(GF, LF, TF, arg1.get_frame())

            val2 = get_arg_value(GF, LF, TF, arg2)

            if figure_out_type(val2) == "int":
                try:
                    out_char = chr(val2)
                except:
                    exit(58)
            else:
                exit(53)

            frame.update(arg1.get_value(), out_char)

        elif instr.get_name() == "STRI2INT":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            frame = figure_out_frame(GF, LF, TF, arg1.get_frame())

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)

            if figure_out_type(val2) == "string" and figure_out_type(val3) == "int":
                if val3 < 0:
                    exit(58)
                try:
                    out = ord(val2[val3])
                except:
                    exit(58)
            else:
                exit(53)

            frame.update(arg1.get_value(), out)

        elif instr.get_name() == "READ":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]

            frame = figure_out_frame(GF, LF, TF, arg1.get_frame())

            val2 = get_arg_value(GF, LF, TF, arg2)

            read = input_file.readline()
            read = read.strip()

            if val2 == "int" or val2 == "float" or val2 == "bool" or val2 == "string":
                try:
                    if val2 == "bool":
                        if read.lower() == "true":
                            out = True
                        elif read != "":
                            out = False
                        else:
                            out = nil()
                    elif val2 == "int":
                        out = int(read)
                    elif val2 == "float":
                        try:
                            out = float(read)
                        except:
                            out = float.fromhex(read)
                    else:
                        out = read
                except:
                    out = nil()
            else:
                exit(55)

            frame.update(arg1.get_value(), out)

        elif instr.get_name() == "WRITE":
            arg = instr.get_arg_list()[0]
            value = get_arg_value(GF, LF, TF, arg)
            if figure_out_type(value) == "nil":
                value = ""
            if figure_out_type(value) == "bool":
                value = str(value).lower()
            if figure_out_type(value) == "float":
                value = value.hex()
            print(str(value), end="")

        elif instr.get_name() == "CONCAT":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)

            if figure_out_type(val2) == "string" and figure_out_type(val3) == "string":
                frame = figure_out_frame(GF, LF, TF, arg1.get_frame())
                tmp = val2 + val3
                frame.update(arg1.get_value(), tmp)
            else:
                exit(53)

        elif instr.get_name() == "STRLEN":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]

            frame = figure_out_frame(GF, LF, TF, arg1.get_frame())

            val2 = get_arg_value(GF, LF, TF, arg2)

            if figure_out_type(val2) == "string":
                frame.update(arg1.get_value(), len(val2))
            else:
                exit(53)

        elif instr.get_name() == "GETCHAR":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            frame = figure_out_frame(GF, LF, TF, arg1.get_frame())

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)

            if figure_out_type(val2) == "string" and figure_out_type(val3) == "int":
                if val3 < 0:
                    exit(58)
                try:
                    out = val2[val3]
                except:
                    exit(58)
            else:
                exit(53)

            frame.update(arg1.get_value(), out)

        elif instr.get_name() == "SETCHAR":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            frame = figure_out_frame(GF, LF, TF, arg1.get_frame())

            val1 = get_arg_value(GF, LF, TF, arg1)
            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)

            if figure_out_type(val1) == "string" and figure_out_type(val2) == "int" and figure_out_type(val3) == "string":
                if val2 < 0:
                    exit(58)
                try:
                    val1 = list(val1)
                    val1[val2] = val3[0]
                    val1 = "".join(val1)
                except:
                    exit(58)
            else:
                exit(53)

            frame.update(arg1.get_value(), val1)

        elif instr.get_name() == "TYPE":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]

            frame = figure_out_frame(GF, LF, TF, arg1.get_frame())

            val2 = get_arg_value(GF, LF, TF, arg2)

            try:
                out = figure_out_type(val2)
            except:
                out = ""

            frame.update(arg1.get_value(), out)

        elif instr.get_name() == "JUMP":
            arg1 = instr.get_arg_list()[0]
            i = labels.get(arg1.get_value())
            continue

        elif instr.get_name() == "JUMPIFEQ":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)

            jump_label = labels.get(arg1.get_value())

            if (figure_out_type(val2) == figure_out_type(val3)) or (figure_out_type(val2) == "nil" or figure_out_type(val3) == "nil"):
                if str(val2) == str(val3):
                    i = jump_label
                    continue
            else:
                exit(53)

        elif instr.get_name() == "JUMPIFNEQ":
            arg1 = instr.get_arg_list()[0]
            arg2 = instr.get_arg_list()[1]
            arg3 = instr.get_arg_list()[2]

            val2 = get_arg_value(GF, LF, TF, arg2)
            val3 = get_arg_value(GF, LF, TF, arg3)

            jump_label = labels.get(arg1.get_value())

            if (figure_out_type(val2) == figure_out_type(val3)) or (figure_out_type(val2) == "nil" or figure_out_type(val3) == "nil"):
                if str(val2) != str(val3):
                    i = jump_label
                    continue
            else:
                exit(53)

        elif instr.get_name() == "EXIT":
            arg1 = instr.get_arg_list()[0]
            val1 = get_arg_value(GF, LF, TF, arg1)

            if type(val1) is int:
                if 0 <= val1 < 50:
                    exit(val1)
                else:
                    exit(57)
            else:
                exit(53)

        elif instr.get_name() == "DPRINT":
            arg = instr.get_arg_list()[0]
            value = get_arg_value(GF, LF, TF, arg)
            if figure_out_type(value) == "bool":
                value = str(value).lower()
            if figure_out_type(value) == "float":
                value = value.hex()
            print(str(value), end="", file=sys.stderr)

        elif instr.get_name() == "BREAK":
            print(instr, file=sys.stderr)
            print(GF, file=sys.stderr)
            print(LF, file=sys.stderr)
            print(TF, file=sys.stderr)


        i = i + 1


argparser = argparse.ArgumentParser()
argparser.add_argument("--source=", help="vstupní soubor s XML reprezentací zdrojového kódu dle definice ze sekce")
argparser.add_argument("--input=", help="soubor se vstupy 12 pro samotnou interpretaci zadaného zdrojového kódu")

args = vars(argparser.parse_args())

source_path = args.get("source=")
input_path = args.get("input=")

# Pokud není zadán ani zdrojoví kód ani input tak se program ukončí
if (source_path is None and input_path is None) or len(sys.argv) == 1:
    exit(10)

if source_path is not None:
    try:
        with open(source_path) as f:
            source_xml = f.read()
    except:
        exit(11)
else:
    source_xml = ""
    for f in sys.stdin:
        if 'q' == f.rstrip():
            break
        source_xml = source_xml + f

global input_file

if input_path is not None:
    try:
        input_file = open(input_path, "r")
    except:
        exit(11)
else:
    input_file = sys.stdin

object_instruction_list = []

# Prochází XML soubor a vkládá instrukce a jejich argumenty (seřazené od 1) do seznamu objektů Instruction
try:
    root = xmltree.fromstring(source_xml)

    for child in root:
        tmp = Instruction(child.attrib.get("opcode"), child.attrib.get("order"))
        object_instruction_list.append(tmp)
        tmp_list = []
        for child_args in child:
            tmp_list.append([child_args.tag, child_args.attrib.get("type"), child_args.text])
        sorted_args = sorted(tmp_list, key=lambda x: x[0])
        for s in sorted_args:
            tmp.add_argument(s[1], s[2])
except xmltree.ParseError as e:
    exit(32)

interpret(object_instruction_list)

input_file.close()

exit(0)
