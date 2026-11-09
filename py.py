import os
import stat

def check_permissions(start_path="."):
    print(f"[*] Starting permissions scan in: {os.path.abspath(start_path)}\n")
    print(f"{'Permissions':<12} | {'Owner/Group':<15} | {'Path'}")
    print("-" * 60)

    for root, dirs, files in os.walk(start_path):
        # تجاهل مجلدات النظام المخفية أو مجلدات الجيت لتسريع الفحص
        if '.git' in root or '__pycache__' in root:
            continue
            
        for name in files + dirs:
            full_path = os.path.join(root, name)
            try:
                file_stat = os.stat(full_path)
                mode = file_stat.st_mode
                
                # تحويل الأذونات إلى صيغة رقمية (مثل 777 أو 644) وصيغة نصية
                perm_octal = oct(stat.S_IMODE(mode))[-3:]
                perm_str = stat.filemode(mode)
                
                # تنبيه إذا كانت الأذونات خطيرة (تسمح للكتابة العامة مثل 777 أو 775 في أماكن حساسة)
                warning = ""
                if perm_octal.endswith('7') or perm_octal.endswith('6'):
                    warning = " [!] WARNING: World Writable"

                print(f"{perm_str} ({perm_octal}) | {'kwbadmin':<15} | {full_path}{warning}")
            except Exception as e:
                # تجاهل الملفات التي ليس لديك صلاحية لقراءتها
                continue

if __name__ == "__main__":
    check_permissions()