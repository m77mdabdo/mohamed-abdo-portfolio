import { execFileSync } from 'node:child_process'

// The contact-form check sends a real message (qa@example.test). Against the local site it is removed again,
// so the owner's inbox never keeps test messages. Remote sites are left alone.
export default function teardown(): void {
  const base = process.env.BASE || 'http://127.0.0.1:8020'
  if (!/^https?:\/\/(127\.0\.0\.1|localhost)(:\d+)?/.test(base)) return
  execFileSync('php', ['artisan', 'tinker', '--execute=App\\Models\\ContactMessage::where("email", "qa@example.test")->delete();'], { stdio: 'ignore' })
}
