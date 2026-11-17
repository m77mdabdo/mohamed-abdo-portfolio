# CI/CD — الاختبارات والنشر التلقائي

الملف: `.github/workflows/ci.yml`. يعمل مع كل push على `main`، ومع كل Pull Request، ويدوياً من تبويب **Actions › CI › Run workflow**.

## ما الذي يُفحص (CI)

| المهمة | الخطوات | تفشل لو |
|---|---|---|
| **Tests** | PHP 8.4 + `composer install` + `npm ci` + `npm run build` + `php artisan test` (SQLite في الذاكرة) | أي اختبار فشل |
| **Lint and static analysis** | `vendor/bin/pint --test` + Larastan (`phpstan`) + `npm run typecheck` | تنسيق أو خطأ نوع |
| **Dependency audit** | `composer audit --locked` + `npm audit --audit-level=moderate` | ظهور ثغرة معروفة في أي مكتبة |

لا تحتاج أي إعداد: الاختبارات لا تستخدم قاعدة بياناتك ولا `.env` الحقيقي.

## النشر التلقائي (CD) — مقفول افتراضياً

مهمة **Deploy to production** تعمل فقط عندما:

1. يكون الحدث push على `main`،
2. وتنجح المهام الثلاث السابقة،
3. ويكون المتغير `DEPLOY_ENABLED` = `true`.

وقتها تدخل السيرفر بـ SSH وتشغّل:

```bash
cd "$DEPLOY_PATH" && git pull --ff-only origin main && bash scripts/deploy.sh
```

و`scripts/deploy.sh` يأخذ نسخة احتياطية للقاعدة قبل أي migration، ويضع الموقع في الصيانة أثناء التحديث فقط، وينتهي بـ `portfolio:preflight`.

### التفعيل (مرة واحدة)

1. على السيرفر (Hostinger › Advanced › SSH Access): أنشئ مفتاح SSH مخصصاً للنشر وأضف الجزء العام إلى `~/.ssh/authorized_keys`.
2. في GitHub: **Settings › Environments › New environment** باسم `production` (يُفضّل تفعيل «Required reviewers» لو عايز توافق على كل نشر).
3. في **Settings › Secrets and variables › Actions**:

| النوع | الاسم | القيمة |
|---|---|---|
| Secret | `DEPLOY_HOST` | عنوان السيرفر |
| Secret | `DEPLOY_PORT` | منفذ SSH (Hostinger غالباً 65002) |
| Secret | `DEPLOY_USER` | مستخدم SSH |
| Secret | `DEPLOY_PATH` | مسار المشروع على السيرفر، مثل `/home/u123/portfolio` |
| Secret | `DEPLOY_SSH_KEY` | المفتاح الخاص لمفتاح النشر |
| Secret | `DEPLOY_KNOWN_HOSTS` | ناتج `ssh-keyscan -p <port> <host>` |
| Variable | `DEPLOY_ENABLED` | `true` |

لإيقاف النشر التلقائي في أي وقت: غيّر `DEPLOY_ENABLED` إلى `false`.
