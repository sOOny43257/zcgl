#!/bin/bash
# =============================================================
# 资产管理系统 - 打印模板 & 系统编码 数据导出脚本
# =============================================================
# 用法: ./export-system-data.sh
#
# 将 打印模板(print_templates) 和 系统编码(department_codes)
# 的数据导出为 SQL 文件，方便迁移到新系统。
# =============================================================

set -e
cd "$(dirname "$0")"

TIMESTAMP=$(date '+%Y%m%d_%H%M%S')
OUTPUT_FILE="${HOME}/Desktop/zcgl_system_data_${TIMESTAMP}.sql"
IMPORT_SCRIPT="${HOME}/Desktop/zcgl_import_system_data_${TIMESTAMP}.sh"

# 加载 .env 中的数据库配置
export $(grep -E "^DB_HOST|^DB_PORT|^DB_DATABASE|^DB_USERNAME|^DB_PASSWORD|^MYSQL_BIN_DIR" .env | xargs 2>/dev/null)

# 自动查找 mysqldump
MYSQLDUMP=""
if [ -n "$MYSQL_BIN_DIR" ] && [ -x "${MYSQL_BIN_DIR}/mysqldump" ]; then
    MYSQLDUMP="${MYSQL_BIN_DIR}/mysqldump"
elif command -v mysqldump &>/dev/null; then
    MYSQLDUMP=$(command -v mysqldump)
elif [ -x /Applications/XAMPP/xamppfiles/bin/mysqldump ]; then
    MYSQLDUMP=/Applications/XAMPP/xamppfiles/bin/mysqldump
elif [ -x /usr/local/mysql/bin/mysqldump ]; then
    MYSQLDUMP=/usr/local/mysql/bin/mysqldump
else
    # 最后尝试：用 php artisan 直接导出
    echo "未找到 mysqldump，使用 PHP 方式导出..."
    php artisan tinker --execute="
        \$backupPath = storage_path('backups');
        if (!is_dir(\$backupPath)) mkdir(\$backupPath, 0777, true);
        \$outputFile = \$backupPath . '/system_data_temp.sql';
        \$fh = fopen(\$outputFile, 'w');

        // 打印模板
        fwrite(\$fh, '-- print_templates' . PHP_EOL);
        fwrite(\$fh, 'TRUNCATE TABLE \`print_templates\`;' . PHP_EOL);
        \$rows = DB::table('print_templates')->get();
        foreach (\$rows as \$r) {
            \$config = addslashes(\$r->config);
            \$updatedBy = \$r->updated_by ?? 'NULL';
            fwrite(\$fh, \"INSERT IGNORE INTO \\\`print_templates\\\` (\\\`id\\\`, \\\`module\\\`, \\\`name\\\`, \\\`orientation\\\`, \\\`page_size\\\`, \\\`config\\\`, \\\`is_active\\\`, \\\`updated_by\\\`, \\\`created_at\\\`, \\\`updated_at\\\`) VALUES (\");
            fwrite(\$fh, \"{\$r->id}, '{\$r->module}', '{\$r->name}', '{\$r->orientation}', '{\$r->page_size}', '{\$config}', {\$r->is_active}, {\$updatedBy}, '{\$r->created_at}', '{\$r->updated_at}'\" . PHP_EOL);
            fwrite(\$fh, ');' . PHP_EOL);
        }

        // 系统编码
        fwrite(\$fh, PHP_EOL . '-- department_codes' . PHP_EOL);
        fwrite(\$fh, 'TRUNCATE TABLE \`department_codes\`;' . PHP_EOL);
        \$rows = DB::table('department_codes')->orderBy('id')->get();
        foreach (\$rows as \$r) {
            fwrite(\$fh, \"INSERT IGNORE INTO \\\`department_codes\\\` (\\\`id\\\`, \\\`type\\\`, \\\`code\\\`, \\\`name\\\`, \\\`created_at\\\`, \\\`updated_at\\\`) VALUES (\");
            fwrite(\$fh, \"{\$r->id}, '{\$r->type}', '{\$r->code}', '{\$r->name}', '{\$r->created_at}', '{\$r->updated_at}'\" . PHP_EOL);
            fwrite(\$fh, ');' . PHP_EOL);
        }

        fclose(\$fh);
        echo \$outputFile;
    " 2>&1 | tail -1 | read TEMP_FILE

    if [ -f "$TEMP_FILE" ]; then
        cp "$TEMP_FILE" "$OUTPUT_FILE"
        rm -f "$TEMP_FILE"
        echo "✓ PHP 方式导出完成"
    else
        echo "✗ 导出失败"
        exit 1
    fi
    exit 0
fi

echo ""
echo "╔══════════════════════════════════════════╗"
echo "║   打印模板 & 系统编码 数据导出             ║"
echo "╚══════════════════════════════════════════╝"
echo ""
echo "mysqldump: ${MYSQLDUMP}"
echo "数据库:    ${DB_DATABASE:-zcgl} @ ${DB_HOST:-127.0.0.1}:${DB_PORT:-3306}"
echo "输出文件:  ${OUTPUT_FILE}"
echo ""

# 创建临时 MySQL 配置文件（避免密码警告）
DB_PASS="${DB_PASSWORD:-}"
CNF_FILE=$(mktemp)
cat > "$CNF_FILE" <<CNF
[client]
user=${DB_USERNAME:-root}
password=${DB_PASS}
CNF

# 导出打印模板（只导数据，不含表结构；使用 INSERT IGNORE）
"$MYSQLDUMP" \
    --defaults-extra-file="$CNF_FILE" \
    -h "${DB_HOST:-127.0.0.1}" \
    -P "${DB_PORT:-3306}" \
    --no-create-info \
    --insert-ignore \
    --complete-insert \
    --skip-add-locks \
    --skip-comments \
    "${DB_DATABASE:-zcgl}" \
    print_templates department_codes \
    2>/dev/null > "$OUTPUT_FILE"

rm -f "$CNF_FILE"

# 检查结果
if [ -f "$OUTPUT_FILE" ] && [ -s "$OUTPUT_FILE" ]; then
    LINE_COUNT=$(wc -l < "$OUTPUT_FILE")
    echo "✓ 导出成功！共 ${LINE_COUNT} 行"
else
    echo "✗ 导出失败，请检查数据库配置"
    rm -f "$OUTPUT_FILE"
    exit 1
fi

echo ""
echo "══════════════════════════════════════════"
echo ""
echo "下一步：将该 SQL 文件导入到新系统。"
echo ""

# 创建导入脚本
cat > "$IMPORT_SCRIPT" << 'SCRIPT'
#!/bin/bash
# =============================================================
# 资产管理系统 - 打印模板 & 系统编码 数据导入脚本
# =============================================================
# 将配套的 SQL 文件导入当前系统的数据库。
#
# 用法: 将此脚本和 .sql 文件放在项目根目录，然后:
#   bash zcgl_import_system_data_*.sh
# =============================================================

set -e
cd "$(dirname "$0")"

# 查找同目录下的 .sql 文件
SQL_FILE=$(ls -1 zcgl_system_data_*.sql 2>/dev/null | head -1)

if [ -z "$SQL_FILE" ]; then
    echo "✗ 未找到 zcgl_system_data_*.sql 数据文件"
    echo "  请将 SQL 文件放在脚本同目录下"
    exit 1
fi

# 加载 .env
export $(grep -E "^DB_HOST|^DB_PORT|^DB_DATABASE|^DB_USERNAME|^DB_PASSWORD|^MYSQL_BIN_DIR" .env | xargs 2>/dev/null)

# 自动查找 mysql 客户端
MYSQL_CLIENT=""
if [ -n "$MYSQL_BIN_DIR" ] && [ -x "${MYSQL_BIN_DIR}/mysql" ]; then
    MYSQL_CLIENT="${MYSQL_BIN_DIR}/mysql"
elif command -v mysql &>/dev/null; then
    MYSQL_CLIENT=$(command -v mysql)
elif [ -x /Applications/XAMPP/xamppfiles/bin/mysql ]; then
    MYSQL_CLIENT=/Applications/XAMPP/xamppfiles/bin/mysql
else
    echo "✗ 未找到 mysql 客户端"
    exit 1
fi

echo ""
echo "╔══════════════════════════════════════════╗"
echo "║    系统数据导入                           ║"
echo "╚══════════════════════════════════════════╝"
echo ""
echo "mysql:      ${MYSQL_CLIENT}"
echo "数据库:     ${DB_DATABASE:-zcgl}"
echo "数据文件:   ${SQL_FILE}"
echo ""

DB_PASS="${DB_PASSWORD:-}"
CNF_FILE=$(mktemp)
cat > "$CNF_FILE" <<CNF
[client]
user=${DB_USERNAME:-root}
password=${DB_PASS}
default-character-set=utf8mb4
CNF

echo "▸ 正在导入 ${SQL_FILE} ..."
"$MYSQL_CLIENT" \
    --defaults-extra-file="$CNF_FILE" \
    -h "${DB_HOST:-127.0.0.1}" \
    -P "${DB_PORT:-3306}" \
    "${DB_DATABASE:-zcgl}" < "$SQL_FILE"

rm -f "$CNF_FILE"

echo ""
echo "══════════════════════════════════════════"
echo "  ✓ 导入完成！"
echo "══════════════════════════════════════════"
echo ""
echo "已导入：打印模板 + 系统编码（部门、类别、状态等）"
echo "（重复数据会自动跳过，不会覆盖已有记录）"
echo ""
SCRIPT

chmod +x "$IMPORT_SCRIPT"

echo "导入脚本:  ${IMPORT_SCRIPT}"
echo ""
echo "迁移步骤:"
echo "  1. 将桌面上的两个文件复制到新系统项目根目录:"
echo "       zcgl_system_data_${TIMESTAMP}.sql"
echo "       zcgl_import_system_data_${TIMESTAMP}.sh"
echo "  2. 确认 .env 已配置好新系统的数据库连接"
echo "  3. 运行: bash zcgl_import_system_data_${TIMESTAMP}.sh"
echo ""
