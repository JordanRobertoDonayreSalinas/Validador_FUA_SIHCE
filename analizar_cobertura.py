import json
import re

# Read the JSON file with documented rules
with open('reglas_extraidas.json', 'r', encoding='utf-8') as f:
    documented_rules = json.load(f)

# Read the PHP service file
with open('app/Services/ReglasValidacionService.php', 'r', encoding='utf-8') as f:
    php_content = f.read()

# Extract all validation function names and their line numbers
pattern = r'function\s+(validar[A-Za-z0-9_]+)\s*\('
matches = re.finditer(pattern, php_content)

implemented_functions = {}
for match in matches:
    func_name = match.group(1)
    # Find line number
    line_num = php_content[:match.start()].count('\n') + 1
    implemented_functions[func_name] = line_num

# Map function names to rule codes
implemented_rules = {}
for func_name, line_num in implemented_functions.items():
    # Extract rule code from function name
    # Examples: validarRC17_DuplicidadFUA -> RC_17, validarRR00_DatosBasicos -> RR_00
    rule_match = re.search(r'validar([A-Z]+)(\d+|_\d+)', func_name)
    if rule_match:
        prefix = rule_match.group(1)
        number = rule_match.group(2).replace('_', '')
        
        # Normalize rule code
        if prefix in ['RC', 'RR', 'RV']:
            # Try different formats
            possible_codes = [
                f'{prefix}_{number.zfill(2)}',  # RC_01
                f'{prefix}{number}',             # RC1
                f'{prefix}_{number}',            # RC_1
            ]
            
            for code in possible_codes:
                if code in documented_rules:
                    implemented_rules[code] = {
                        'function': func_name,
                        'line': line_num
                    }
                    break

# Get all documented rule codes
all_documented = sorted(documented_rules.keys())

# Separate implemented and missing
implemented_codes = sorted(implemented_rules.keys())
missing_codes = [code for code in all_documented if code not in implemented_codes]

# Print results
print("="*80)
print("ANÁLISIS DE COBERTURA DE REGLAS DE AUDITORÍA FUA")
print("="*80)
print(f"\nTotal reglas documentadas: {len(all_documented)}")
print(f"Total reglas implementadas: {len(implemented_codes)}")
print(f"Total reglas faltantes: {len(missing_codes)}")
print(f"Porcentaje de cobertura: {len(implemented_codes)/len(all_documented)*100:.1f}%")

print("\n" + "="*80)
print("✅ REGLAS IMPLEMENTADAS ({})".format(len(implemented_codes)))
print("="*80)
for code in implemented_codes:
    info = implemented_rules[code]
    print(f"  {code:10} -> {info['function']:40} (línea {info['line']})")

print("\n" + "="*80)
print("❌ REGLAS FALTANTES ({})".format(len(missing_codes)))
print("="*80)

# Group missing rules by type
rc_missing = [r for r in missing_codes if r.startswith('RC')]
rr_missing = [r for r in missing_codes if r.startswith('RR')]
rv_missing = [r for r in missing_codes if r.startswith('RV')]

if rc_missing:
    print(f"\n  Reglas de Consistencia (RC): {len(rc_missing)}")
    for code in rc_missing:
        print(f"    - {code}")

if rr_missing:
    print(f"\n  Reglas de Registro (RR): {len(rr_missing)}")
    for code in rr_missing:
        print(f"    - {code}")

if rv_missing:
    print(f"\n  Reglas de Validación (RV): {len(rv_missing)}")
    for code in rv_missing:
        print(f"    - {code}")

print("\n" + "="*80)
print("FUNCIONES IMPLEMENTADAS QUE NO MAPEAN A REGLAS DOCUMENTADAS")
print("="*80)
for func_name in implemented_functions.keys():
    if not any(func_name == info['function'] for info in implemented_rules.values()):
        if 'validar' in func_name.lower():
            print(f"  - {func_name} (línea {implemented_functions[func_name]})")

print("\n" + "="*80)
