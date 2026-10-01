<?php
// index.php
// LMNTrix Main Private Clubhouse Application Shell

require_once __DIR__ . '/includes/auth.php';

$currentUser = get_current_user_data();
$csrfToken = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#e4ebf0">
<link rel="manifest" href="manifest.json">
<title>LMNTRIX — Private Space. Inner Circle.</title>
<style>
/* ============================================================
   LMNTRIX — NEUMORPHIC SYSTEM
   ============================================================ */
:root{
  --bg:#e4ebf0;
  --text:#3a4756;
  --text-muted:#6d7b8d;
  --text-dim:#9aa6b5;
  --accent:#4a90c7;
  --accent-deep:#2d6b9e;
  --online:#4fb87a;
  --offline:#b8c1cc;
  --shadow-dark:#a3b1c6;
  --shadow-light:#ffffff;
  --radius:20px;

  --clay:1;
  --anim:1;
  --dur:calc(.22s * var(--anim));
  --dur-slow:calc(.4s * var(--anim));
}

*,*::before,*::after{box-sizing:border-box;}
html,body{height:100%;}
body{
  margin:0;
  background:var(--bg);
  color:var(--text);
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
  font-size:15px;
  line-height:1.5;
  overflow:hidden;
  -webkit-font-smoothing:antialiased;
  -moz-osx-font-smoothing:grayscale;
  overscroll-behavior:none;
}

/* Ambient light */
body::before{
  content:"";position:fixed;inset:0;z-index:0;pointer-events:none;
  background:
    radial-gradient(1400px 900px at 10% -10%, rgba(255,255,255,.55), transparent 62%),
    radial-gradient(1200px 800px at 110% 112%, rgba(163,177,198,.35), transparent 60%);
}

.sr-only{
  position:absolute;width:1px;height:1px;padding:0;margin:-1px;
  overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;border:0;
}
.tech-label,.tech-pill{
  font-size:9.5px;letter-spacing:.18em;font-weight:600;
  text-transform:uppercase;color:var(--text-dim);
}
.tech-pill{
  display:inline-flex;align-items:center;gap:7px;
  padding:7px 12px;border-radius:11px;
  background:var(--bg);
  box-shadow:
    calc(3px * var(--clay)) calc(3px * var(--clay)) calc(7px * var(--clay)) var(--shadow-dark),
    calc(-3px * var(--clay)) calc(-3px * var(--clay)) calc(7px * var(--clay)) var(--shadow-light);
}
.tech-pill .dot{
  width:6px;height:6px;border-radius:50%;background:var(--online);
  box-shadow:0 0 8px rgba(79,184,122,.5);
}

.clay{
  background:var(--bg);
  border-radius:var(--radius);
  box-shadow:
    calc(7px * var(--clay)) calc(7px * var(--clay)) calc(16px * var(--clay)) var(--shadow-dark),
    calc(-7px * var(--clay)) calc(-7px * var(--clay)) calc(16px * var(--clay)) var(--shadow-light);
}

/* ============================================================
   APP SHELL
   ============================================================ */
.app{
  position:relative;z-index:2;
  height:100dvh;
  display:grid;
  grid-template-rows:auto minmax(0,1fr);
  gap:14px;
  padding:14px;
  opacity:0;
  transform:scale(.99);
  transition:opacity calc(.8s * var(--anim)) ease, transform calc(.8s * var(--anim)) ease;
}
.app.ready{opacity:1;transform:scale(1);}

/* Topbar */
.topbar{
  position:relative;
  display:flex;align-items:center;justify-content:space-between;
  gap:16px;
  padding:12px 14px 12px 18px;
  border-radius:18px;
  z-index:40;
}
.brand{display:flex;align-items:center;gap:13px;min-width:0;}
.brand-mark{
  width:34px;height:34px;border-radius:11px;flex:none;
  display:grid;place-items:center;
  font-size:14px;font-weight:700;letter-spacing:.02em;
  color:#ffffff;
  background:linear-gradient(145deg,#5a9ed1,#3a7db3);
  box-shadow:
    calc(3px * var(--clay)) calc(3px * var(--clay)) calc(7px * var(--clay)) rgba(45,107,158,.4),
    calc(-3px * var(--clay)) calc(-3px * var(--clay)) calc(7px * var(--clay)) rgba(255,255,255,.85);
}
.brand-text{display:flex;align-items:baseline;gap:9px;min-width:0;}
.brand-name{
  font-size:14px;font-weight:600;letter-spacing:.28em;
  white-space:nowrap;
  color:var(--text);
}
.brand-sub{
  font-size:9px;letter-spacing:.2em;color:var(--text-dim);
  font-weight:600;white-space:nowrap;
}
.topbar-actions{display:flex;align-items:center;gap:10px;}

/* Search */
.search-box{display:flex;align-items:center;gap:8px;}
.search-input{
  width:0;opacity:0;padding:0;border:none;
  height:36px;border-radius:12px;
  background:var(--bg);
  box-shadow:
    inset calc(3px * var(--clay)) calc(3px * var(--clay)) calc(7px * var(--clay)) var(--shadow-dark),
    inset calc(-3px * var(--clay)) calc(-3px * var(--clay)) calc(7px * var(--clay)) var(--shadow-light);
  color:var(--text);font:inherit;font-size:13.5px;
  outline:none;
  transition:width var(--dur-slow) cubic-bezier(.22,.8,.3,1), opacity var(--dur) ease, padding var(--dur-slow) ease;
}
.search-box.open .search-input{
  width:min(280px,38vw);opacity:1;padding:0 14px;
}
.search-input::placeholder{color:var(--text-dim);}

.search-results{
  position:absolute;top:calc(100% + 10px);right:14px;
  width:min(430px,calc(100vw - 28px));
  max-height:min(52vh,440px);overflow-y:auto;
  padding:10px;
  border-radius:18px;
  z-index:60;
  background:var(--bg);
  box-shadow:
    calc(10px * var(--clay)) calc(10px * var(--clay)) calc(24px * var(--clay)) var(--shadow-dark),
    calc(-6px * var(--clay)) calc(-6px * var(--clay)) calc(16px * var(--clay)) var(--shadow-light);
}

/* Buttons */
.icon-btn{
  display:inline-flex;align-items:center;justify-content:center;gap:6px;
  padding:9px 13px;
  border:none;border-radius:12px;cursor:pointer;
  font-family:inherit;font-size:9.5px;font-weight:600;
  letter-spacing:.16em;text-transform:uppercase;
  color:var(--text-muted);
  background:var(--bg);
  box-shadow:
    calc(3px * var(--clay)) calc(3px * var(--clay)) calc(7px * var(--clay)) var(--shadow-dark),
    calc(-3px * var(--clay)) calc(-3px * var(--clay)) calc(7px * var(--clay)) var(--shadow-light);
  transition:box-shadow var(--dur) ease, color var(--dur) ease;
  -webkit-tap-highlight-color:transparent;
}
.icon-btn:hover{color:var(--text);}
.icon-btn:active{
  box-shadow:
    inset calc(3px * var(--clay)) calc(3px * var(--clay)) calc(7px * var(--clay)) var(--shadow-dark),
    inset calc(-3px * var(--clay)) calc(-3px * var(--clay)) calc(7px * var(--clay)) var(--shadow-light);
}
.icon-btn.round{padding:0;width:38px;height:38px;font-size:15px;letter-spacing:0;}

.profile-btn{
  width:38px;height:38px;border-radius:13px;flex:none;border:none;cursor:pointer;
  font-family:inherit;font-size:13px;font-weight:700;color:#ffffff;
  background:linear-gradient(145deg,#5a9ed1,#3a7db3);
  box-shadow:
    calc(4px * var(--clay)) calc(4px * var(--clay)) calc(9px * var(--clay)) rgba(45,107,158,.38),
    calc(-4px * var(--clay)) calc(-4px * var(--clay)) calc(9px * var(--clay)) rgba(255,255,255,.85);
}

/* Shell Layout */
.shell{
  display:grid;
  grid-template-columns:minmax(230px,292px) minmax(0,1fr);
  gap:14px;
  min-height:0;
}

/* Sidebar */
.sidebar{
  display:flex;flex-direction:column;
  min-height:0;
  padding:16px 8px 12px 16px;
  border-radius:18px;
}
.sidebar-head{padding-right:10px;padding-bottom:14px;}
.side-search{
  width:100%;height:38px;padding:0 14px;
  border:none;border-radius:13px;
  background:var(--bg);
  box-shadow:
    inset calc(3px * var(--clay)) calc(3px * var(--clay)) calc(7px * var(--clay)) var(--shadow-dark),
    inset calc(-3px * var(--clay)) calc(-3px * var(--clay)) calc(7px * var(--clay)) var(--shadow-light);
  color:var(--text);font:inherit;font-size:13px;outline:none;
}
.side-search::placeholder{color:var(--text-dim);}

.sidebar-scroll{
  flex:1 1 auto;min-height:0;overflow-y:auto;
  padding-right:8px;margin-right:-2px;
}
.list-section{margin-bottom:22px;}
.section-title{
  display:flex;align-items:center;gap:8px;
  font-size:9px;letter-spacing:.22em;font-weight:600;
  color:var(--text-dim);text-transform:uppercase;
  margin:0 0 9px;padding-left:8px;
}
.conv-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:4px;}

.conv{
  display:flex;align-items:center;gap:11px;width:100%;
  padding:9px 10px;border:none;border-radius:14px;
  background:transparent;color:var(--text);
  font:inherit;text-align:left;cursor:pointer;
  transition:box-shadow var(--dur) ease, background var(--dur) ease;
}
.conv:hover,.conv[aria-current="true"]{
  background:var(--bg);
  box-shadow:
    inset calc(3px * var(--clay)) calc(3px * var(--clay)) calc(7px * var(--clay)) var(--shadow-dark),
    inset calc(-3px * var(--clay)) calc(-3px * var(--clay)) calc(7px * var(--clay)) var(--shadow-light);
}
.conv-body{min-width:0;flex:1;}
.conv-name{
  font-size:13.5px;font-weight:600;color:var(--text);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.conv-last{
  display:block;font-size:12px;color:var(--text-muted);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  margin-top:2px;
}

/* Avatars */
.avatar{
  position:relative;flex:none;
  width:38px;height:38px;border-radius:12px;
  display:grid;place-items:center;
  font-size:13px;font-weight:600;letter-spacing:.03em;
  color:#ffffff;
  background:linear-gradient(145deg,
    hsl(var(--h,220) 55% 62%),
    hsl(var(--h,220) 50% 45%));
  box-shadow:
    calc(2px * var(--clay)) calc(2px * var(--clay)) calc(5px * var(--clay)) var(--shadow-dark),
    calc(-2px * var(--clay)) calc(-2px * var(--clay)) calc(5px * var(--clay)) var(--shadow-light);
}
.avatar.sm{width:30px;height:30px;border-radius:10px;font-size:11px;}
.avatar.xs{width:23px;height:23px;border-radius:8px;font-size:9.5px;}
.avatar.lg{width:54px;height:54px;border-radius:16px;font-size:18px;}
.avatar .status-dot{
  position:absolute;right:-2px;bottom:-2px;
  width:11px;height:11px;border-radius:50%;
  border:2.5px solid var(--bg);
  background:var(--offline);
}
.avatar .status-dot.online{background:var(--online);box-shadow:0 0 6px rgba(79,184,122,.55);}

.sidebar-foot{
  display:flex;justify-content:space-between;gap:10px;
  padding:14px 12px 2px 8px;
  box-shadow:0 -6px 10px -10px var(--shadow-dark);
  margin-top:6px;
}

/* Chat Panel */
.chat{
  display:flex;flex-direction:column;
  min-height:0;
  border-radius:18px;
  overflow:hidden;
}
.chat-head{
  display:flex;align-items:center;gap:13px;
  padding:13px 16px;
  flex:none;
  box-shadow:0 8px 14px -12px var(--shadow-dark);
  z-index:5;
}
.chat-id{display:flex;align-items:center;gap:12px;min-width:0;flex:1;}
.chat-meta{min-width:0;}
.chat-meta h2{
  margin:0;font-size:15px;font-weight:600;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.chat-status{
  display:flex;align-items:center;gap:6px;
  margin:2px 0 0;font-size:10px;letter-spacing:.14em;
  text-transform:uppercase;font-weight:600;color:var(--text-dim);
}
.chat-status .dot{
  width:6px;height:6px;border-radius:50%;background:var(--offline);
}
.chat-status .dot.online{background:var(--online);box-shadow:0 0 6px rgba(79,184,122,.6);}

.pinned-banner{
  background:var(--bg);
  padding:8px 16px;
  font-size:12px;
  border-bottom:1px solid rgba(163,177,198,.3);
  display:flex;align-items:center;justify-content:space-between;
}

.messages{
  flex:1 1 auto;min-height:0;
  overflow-y:auto;
  padding:26px 24px 12px;
  display:flex;flex-direction:column;gap:13px;
  scroll-behavior:smooth;
  overscroll-behavior:contain;
}

.msg-row{
  display:flex;align-items:flex-end;gap:9px;
  animation:msgIn calc(.3s * var(--anim)) cubic-bezier(.22,.8,.3,1) both;
}
.msg-row.me{flex-direction:row-reverse;}
@keyframes msgIn{
  from{opacity:0;transform:translateY(9px) scale(.985)}
  to{opacity:1;transform:none}
}

.msg-col{display:flex;flex-direction:column;max-width:min(75%,560px);min-width:0;}
.msg-row.me .msg-col{align-items:flex-end;}
.sender{
  font-size:10px;letter-spacing:.1em;font-weight:600;
  color:var(--accent-deep);opacity:.85;
  text-transform:uppercase;
  margin:0 0 6px 6px;
}
.bubble{
  position:relative;
  padding:12px 16px 9px;
  border-radius:16px;
  font-size:14.5px;line-height:1.55;
  word-wrap:break-word;overflow-wrap:anywhere;
  color:var(--text);
  background:var(--bg);
  box-shadow:
    calc(4px * var(--clay)) calc(4px * var(--clay)) calc(9px * var(--clay)) var(--shadow-dark),
    calc(-4px * var(--clay)) calc(-4px * var(--clay)) calc(9px * var(--clay)) var(--shadow-light);
}
.msg-row.them .bubble{border-top-left-radius:6px;}
.msg-row.me .bubble{
  border-top-right-radius:6px;
  background:var(--bg);
  box-shadow:
    inset calc(4px * var(--clay)) calc(4px * var(--clay)) calc(9px * var(--clay)) var(--shadow-dark),
    inset calc(-4px * var(--clay)) calc(-4px * var(--clay)) calc(9px * var(--clay)) var(--shadow-light);
}
.bubble p{margin:0;white-space:pre-wrap;}
.msg-time{
  display:block;text-align:right;
  font-size:9px;letter-spacing:.1em;font-weight:600;
  color:var(--text-dim);margin-top:6px;
}

.reply-preview{
  font-size:11px;
  padding:4px 8px;
  background:rgba(74,144,199,.15);
  border-left:3px solid var(--accent);
  border-radius:4px;
  margin-bottom:6px;
}

/* Reactions Bar */
.msg-reactions{
  display:flex;flex-wrap:wrap;gap:4px;margin-top:4px;
}
.reaction-pill{
  display:inline-flex;align-items:center;gap:4px;
  padding:3px 8px;border-radius:10px;border:none;
  font-size:11px;background:var(--bg);cursor:pointer;
  box-shadow:
    calc(2px * var(--clay)) calc(2px * var(--clay)) calc(5px * var(--clay)) var(--shadow-dark),
    calc(-2px * var(--clay)) calc(-2px * var(--clay)) calc(5px * var(--clay)) var(--shadow-light);
}
.reaction-pill.reacted{
  color:var(--accent-deep);
  box-shadow:
    inset calc(2px * var(--clay)) calc(2px * var(--clay)) calc(5px * var(--clay)) var(--shadow-dark),
    inset calc(-2px * var(--clay)) calc(-2px * var(--clay)) calc(5px * var(--clay)) var(--shadow-light);
}

.msg-actions{
  display:flex;gap:6px;margin-top:4px;font-size:11px;
}
.msg-actions button{
  border:none;background:none;cursor:pointer;color:var(--text-muted);padding:0 2px;
}
.msg-actions button:hover{color:var(--text);}

/* Active Reply Bar */
.active-reply-bar{
  display:flex;align-items:center;justify-content:space-between;
  padding:6px 16px;background:rgba(74,144,199,.1);font-size:12px;
}

/* Composer */
.composer{
  position:relative;flex:none;
  padding:8px 16px 16px;
}
.composer-inner{
  display:flex;align-items:flex-end;gap:10px;
  padding:8px 8px 8px 18px;
  border-radius:20px;
  background:var(--bg);
  box-shadow:
    inset calc(4px * var(--clay)) calc(4px * var(--clay)) calc(9px * var(--clay)) var(--shadow-dark),
    inset calc(-4px * var(--clay)) calc(-4px * var(--clay)) calc(9px * var(--clay)) var(--shadow-light);
}
.composer textarea{
  flex:1 1 auto;min-width:0;
  background:none;border:none;outline:none;resize:none;
  color:var(--text);font:inherit;font-size:14.5px;line-height:1.5;
  padding:10px 0;max-height:132px;
}
.composer textarea::placeholder{color:var(--text-dim);}

.send-btn{
  width:42px;height:42px;flex:none;
  border:none;border-radius:14px;cursor:pointer;
  display:grid;place-items:center;
  font-size:17px;font-weight:700;color:#ffffff;
  background:linear-gradient(145deg,#5a9ed1,#3a7db3);
  box-shadow:
    calc(4px * var(--clay)) calc(4px * var(--clay)) calc(9px * var(--clay)) rgba(45,107,158,.4),
    calc(-4px * var(--clay)) calc(-4px * var(--clay)) calc(9px * var(--clay)) rgba(255,255,255,.85);
}

/* Modals & Popups */
.popmenu{
  position:absolute;top:calc(100% + 8px);right:0;
  min-width:200px;padding:8px;border-radius:16px;z-index:50;
  background:var(--bg);
  box-shadow:
    calc(10px * var(--clay)) calc(10px * var(--clay)) calc(24px * var(--clay)) var(--shadow-dark),
    calc(-6px * var(--clay)) calc(-6px * var(--clay)) calc(16px * var(--clay)) var(--shadow-light);
}
.popmenu[hidden]{display:none;}
.popmenu button{
  display:block;width:100%;text-align:left;
  padding:10px 12px;border:none;border-radius:11px;
  background:none;cursor:pointer;font-family:inherit;
  font-size:12.5px;color:var(--text-muted);
}

.panel{
  position:fixed;top:14px;right:14px;bottom:14px;
  width:min(340px,calc(100vw - 28px));
  z-index:80;
  display:flex;flex-direction:column;
  border-radius:20px;
  transform:translateX(calc(100% + 24px));
  opacity:0;
  transition:transform var(--dur-slow) cubic-bezier(.22,.8,.3,1), opacity var(--dur-slow) ease;
  background:var(--bg);
  box-shadow:
    calc(12px * var(--clay)) calc(12px * var(--clay)) calc(28px * var(--clay)) var(--shadow-dark),
    calc(-8px * var(--clay)) calc(-8px * var(--clay)) calc(20px * var(--clay)) var(--shadow-light);
}
.panel.open{transform:none;opacity:1;}
.panel-head{display:flex;align-items:center;justify-content:space-between;padding:16px;}
.panel-body{flex:1;overflow-y:auto;padding:6px 18px 22px;}

.modal{
  position:fixed;z-index:95;
  left:50%;top:50%;
  width:min(660px,calc(100vw - 28px));
  max-height:min(84dvh,680px);
  display:flex;flex-direction:column;
  border-radius:22px;
  transform:translate(-50%,-50%) scale(.97);
  opacity:0;pointer-events:none;
  background:var(--bg);
  box-shadow:
    calc(16px * var(--clay)) calc(16px * var(--clay)) calc(38px * var(--clay)) var(--shadow-dark),
    calc(-10px * var(--clay)) calc(-10px * var(--clay)) calc(26px * var(--clay)) var(--shadow-light);
}
.modal.open{transform:translate(-50%,-50%) scale(1);opacity:1;pointer-events:auto;}
.modal-head{display:flex;align-items:center;justify-content:space-between;padding:20px;}
.modal-body{flex:1;overflow-y:auto;padding:0 20px 24px;}

.toast{
  position:fixed;left:50%;bottom:28px;z-index:150;
  transform:translate(-50%,20px);
  padding:12px 22px;border-radius:14px;
  font-size:11.5px;letter-spacing:.06em;color:var(--text-muted);
  background:var(--bg);
  box-shadow:
    calc(6px * var(--clay)) calc(6px * var(--clay)) calc(14px * var(--clay)) var(--shadow-dark),
    calc(-4px * var(--clay)) calc(-4px * var(--clay)) calc(10px * var(--clay)) var(--shadow-light);
  opacity:0;pointer-events:none;
  transition:all .2s ease;
}
.toast.show{opacity:1;transform:translate(-50%,0);}

.input-field{
  width:100%;padding:10px 12px;border:none;border-radius:12px;background:var(--bg);
  box-shadow:inset 3px 3px 7px var(--shadow-dark), inset -3px -3px 7px var(--shadow-light);
  outline:none;margin-bottom:10px;color:var(--text);font:inherit;
}

@media (max-width:900px){
  .app{padding:10px;gap:10px;}
  .shell{grid-template-columns:minmax(0,1fr);gap:10px;}
  .sidebar{
    position:fixed;top:0;left:0;bottom:0;
    width:min(86vw,340px);
    z-index:70;
    border-radius:0 22px 22px 0;
    padding:20px 8px 16px 18px;
    transform:translateX(-102%);
    background:var(--bg);
    box-shadow:calc(12px * var(--clay)) 0 calc(30px * var(--clay)) var(--shadow-dark);
  }
  .sidebar.open{transform:none;}
}
</style>
</head>
<body>

<!-- Auth Modal (if not logged in) -->
<?php if (!$currentUser): ?>
<div class="modal open" id="authModal" style="pointer-events:auto;z-index:300;">
  <div class="modal-head">
    <h2 id="authTitle">LMNTRIX LOGIN</h2>
  </div>
  <div class="modal-body">
    <form id="loginForm">
      <div style="margin-bottom:12px;">
        <label style="font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);">Username or Email</label>
        <input type="text" id="loginIdentity" class="input-field" required>
      </div>
      <div style="margin-bottom:18px;">
        <label style="font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);">Password</label>
        <input type="password" id="loginPassword" class="input-field" required>
      </div>
      <button type="submit" class="send-btn" style="width:100%;height:44px;border-radius:14px;font-size:13px;letter-spacing:.15em;text-transform:uppercase;">Enter LMNTrix</button>
      <div style="margin-top:16px;text-align:center;">
        <button type="button" id="toggleAuthBtn" style="background:none;border:none;color:var(--accent);cursor:pointer;font-size:12px;">Need an account? Register</button>
      </div>
    </form>

    <form id="registerForm" hidden>
      <div style="margin-bottom:12px;">
        <label style="font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);">Username</label>
        <input type="text" id="regUsername" class="input-field" required>
      </div>
      <div style="margin-bottom:12px;">
        <label style="font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);">Display Name</label>
        <input type="text" id="regDisplayName" class="input-field" required>
      </div>
      <div style="margin-bottom:12px;">
        <label style="font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);">Email</label>
        <input type="email" id="regEmail" class="input-field" required>
      </div>
      <div style="margin-bottom:12px;">
        <label style="font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);">Password</label>
        <input type="password" id="regPassword" class="input-field" required>
      </div>
      <div style="margin-bottom:18px;">
        <label style="font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);">Confirm Password</label>
        <input type="password" id="regConfirmPassword" class="input-field" required>
      </div>
      <button type="submit" class="send-btn" style="width:100%;height:44px;border-radius:14px;font-size:13px;letter-spacing:.15em;text-transform:uppercase;">Create Account</button>
      <div style="margin-top:16px;text-align:center;">
        <button type="button" id="toggleAuthBtn2" style="background:none;border:none;color:var(--accent);cursor:pointer;font-size:12px;">Already have an account? Login</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- App Shell -->
<div class="app ready" id="app">

  <!-- Topbar -->
  <header class="topbar clay">
    <div class="brand">
      <div class="brand-mark">L</div>
      <div class="brand-text">
        <span class="brand-name">LMNTRIX</span>
        <span class="brand-sub">// PRIVATE</span>
      </div>
    </div>

    <div class="topbar-actions">
      <span class="tech-pill">
        <i class="dot" aria-hidden="true"></i>ONLINE
      </span>

      <div class="search-box" id="searchBox">
        <button class="icon-btn" id="searchBtn"><span>Search</span></button>
        <input id="searchInput" class="search-input" type="search" placeholder="Search messages..." autocomplete="off">
      </div>

      <button class="profile-btn" id="profileBtn"><?php echo $currentUser ? htmlspecialchars(strtoupper(substr($currentUser['display_name'], 0, 1))) : '?'; ?></button>
      <button class="icon-btn menu-btn" id="menuBtn"><span>Menu</span></button>
    </div>

    <div class="search-results" id="searchResults" hidden></div>
  </header>

  <!-- Shell -->
  <div class="shell">

    <!-- Sidebar -->
    <aside class="sidebar clay" id="sidebar">
      <div class="sidebar-head">
        <input id="sideSearch" class="side-search" type="search" placeholder="Filter friends..." autocomplete="off">
      </div>

      <div class="sidebar-scroll" id="sidebarScroll">
        <section class="list-section">
          <h2 class="section-title">Members (<span id="memberCount">0</span>)</h2>
          <ul class="conv-list" id="memberList"></ul>
        </section>
      </div>

      <div class="sidebar-foot">
        <span class="tech-label">LMNTRIX PRIVATE V1.0</span>
      </div>
    </aside>

    <!-- Chat -->
    <section class="chat clay" id="chat">

      <header class="chat-head">
        <div class="chat-id">
          <div class="avatar" id="chatAvatar" style="--h:205">L</div>
          <div class="chat-meta">
            <h2 id="chatName">LMNTRIX Group Chat</h2>
            <p class="chat-status" id="chatStatus"><i class="dot online"></i><span>Active Clubhouse</span></p>
          </div>
        </div>

        <div class="chat-head-actions">
          <?php if ($currentUser && in_array($currentUser['role'], ['admin', 'owner'], true)): ?>
            <a href="admin/index.php" class="icon-btn" style="text-decoration:none;"><span>Admin</span></a>
          <?php endif; ?>
          <button class="icon-btn" id="moreBtn"><span>More</span></button>
          <div class="popmenu" id="moreMenu" hidden>
            <button data-action="logout">Logout</button>
          </div>
        </div>
      </header>

      <!-- Pinned Message Banner -->
      <div id="pinnedBanner" class="pinned-banner" hidden>
        <span>📌 <strong id="pinnedText">Pinned Message</strong></span>
      </div>

      <div class="messages" id="messages" role="log"></div>

      <!-- Typing Indicator -->
      <div id="typingIndicator" style="padding:4px 16px;font-size:11px;color:var(--text-dim);font-style:italic;height:18px;"></div>

      <!-- Active Reply Preview Bar -->
      <div id="activeReplyBar" class="active-reply-bar" hidden>
        <span>Replying to <strong id="replyTargetUser">...</strong></span>
        <button type="button" onclick="cancelReply()" style="border:none;background:none;cursor:pointer;">✕</button>
      </div>

      <form class="composer" id="composer" autocomplete="off">
        <div class="composer-inner">
          <textarea id="input" rows="1" placeholder="Type something..."></textarea>
          <div class="composer-actions">
            <button type="button" class="icon-btn round" id="attachBtn">＋</button>
            <button type="submit" class="send-btn" id="sendBtn">↑</button>
          </div>
        </div>
      </form>

    </section>
  </div>
</div>

<!-- Profile Panel -->
<aside class="panel" id="profilePanel">
  <div class="panel-head">
    <span class="tech-label">Profile & Settings</span>
    <button class="icon-btn round" id="closeProfile">✕</button>
  </div>
  <div class="panel-body">
    <div style="text-align:center;padding:12px 0;">
      <div class="avatar lg" style="margin:0 auto 10px;--h:205;"><?php echo $currentUser ? htmlspecialchars(strtoupper(substr($currentUser['display_name'], 0, 1))) : '?'; ?></div>
      <h3><?php echo htmlspecialchars($currentUser['display_name'] ?? 'Guest'); ?></h3>
      <span style="font-size:11px;color:var(--text-dim);">@<?php echo htmlspecialchars($currentUser['username'] ?? 'guest'); ?> · <?php echo strtoupper($currentUser['role'] ?? 'member'); ?></span>
    </div>

    <?php if ($currentUser): ?>
    <form id="profileForm" style="margin-top:14px;">
      <label style="font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);">Display Name</label>
      <input type="text" id="profileDisplayName" class="input-field" value="<?php echo htmlspecialchars($currentUser['display_name']); ?>" required>

      <label style="font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);">Status</label>
      <input type="text" id="profileStatus" class="input-field" value="<?php echo htmlspecialchars($currentUser['status'] ?? ''); ?>">

      <label style="font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);">Bio</label>
      <textarea id="profileBio" class="input-field" rows="2"><?php echo htmlspecialchars($currentUser['bio'] ?? ''); ?></textarea>

      <button type="submit" class="send-btn" style="width:100%;height:38px;border-radius:12px;font-size:11px;letter-spacing:.15em;margin-top:6px;">SAVE PROFILE</button>
    </form>

    <form id="passwordForm" style="margin-top:20px;border-top:1px solid rgba(163,177,198,.3);padding-top:14px;">
      <label style="font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);">Current Password</label>
      <input type="password" id="currentPassword" class="input-field" required>

      <label style="font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);">New Password</label>
      <input type="password" id="newPassword" class="input-field" required>

      <label style="font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);">Confirm New Password</label>
      <input type="password" id="confirmNewPassword" class="input-field" required>

      <button type="submit" class="send-btn" style="width:100%;height:38px;border-radius:12px;font-size:11px;letter-spacing:.15em;margin-top:6px;">CHANGE PASSWORD</button>
    </form>
    <?php endif; ?>

    <div style="margin-top:20px;">
      <button class="send-btn" id="logoutBtn" style="width:100%;height:40px;border-radius:12px;font-size:12px;letter-spacing:.15em;">LOGOUT</button>
    </div>
  </div>
</aside>

<div class="toast" id="toast"></div>

<script>
const CSRF_TOKEN = "<?php echo $csrfToken; ?>";
const CURRENT_USER_ID = <?php echo $currentUser ? $currentUser['id'] : 0; ?>;
const IS_ADMIN = <?php echo ($currentUser && in_array($currentUser['role'], ['admin', 'owner'], true)) ? 'true' : 'false'; ?>;

let lastMsgId = 0;
let activeReplyId = null;

function esc(str) {
  return String(str || '').replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[c]));
}

function showToast(msg) {
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 2500);
}

// Auth Switchers
const toggleAuthBtn = document.getElementById('toggleAuthBtn');
const toggleAuthBtn2 = document.getElementById('toggleAuthBtn2');
if (toggleAuthBtn) {
  toggleAuthBtn.addEventListener('click', () => {
    document.getElementById('loginForm').hidden = true;
    document.getElementById('registerForm').hidden = false;
    document.getElementById('authTitle').textContent = 'CREATE LMNTRIX ACCOUNT';
  });
  toggleAuthBtn2.addEventListener('click', () => {
    document.getElementById('registerForm').hidden = true;
    document.getElementById('loginForm').hidden = false;
    document.getElementById('authTitle').textContent = 'LMNTRIX LOGIN';
  });
}

// Login
const loginForm = document.getElementById('loginForm');
if (loginForm) {
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const res = await fetch('auth/login.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        csrf_token: CSRF_TOKEN,
        identity: document.getElementById('loginIdentity').value,
        password: document.getElementById('loginPassword').value
      })
    });
    const data = await res.json();
    if (data.success) {
      window.location.reload();
    } else {
      alert(data.error || 'Login failed');
    }
  });
}

// Register
const registerForm = document.getElementById('registerForm');
if (registerForm) {
  registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const res = await fetch('auth/register.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        csrf_token: CSRF_TOKEN,
        username: document.getElementById('regUsername').value,
        display_name: document.getElementById('regDisplayName').value,
        email: document.getElementById('regEmail').value,
        password: document.getElementById('regPassword').value,
        confirm_password: document.getElementById('regConfirmPassword').value
      })
    });
    const data = await res.json();
    if (data.success) {
      window.location.reload();
    } else {
      alert(data.error || 'Registration failed');
    }
  });
}

// Profile & Settings
document.getElementById('profileForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const res = await fetch('api/profile.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      csrf_token: CSRF_TOKEN,
      display_name: document.getElementById('profileDisplayName').value,
      status: document.getElementById('profileStatus').value,
      bio: document.getElementById('profileBio').value
    })
  });
  const data = await res.json();
  if (data.success) {
    showToast('Profile updated!');
    setTimeout(() => window.location.reload(), 1000);
  } else {
    alert(data.error || 'Failed to update profile');
  }
});

document.getElementById('passwordForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const res = await fetch('api/profile.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      csrf_token: CSRF_TOKEN,
      action: 'change_password',
      current_password: document.getElementById('currentPassword').value,
      new_password: document.getElementById('newPassword').value,
      confirm_password: document.getElementById('confirmNewPassword').value
    })
  });
  const data = await res.json();
  if (data.success) {
    showToast('Password changed!');
    document.getElementById('passwordForm').reset();
  } else {
    alert(data.error || 'Failed to change password');
  }
});

// Logout
async function doLogout() {
  await fetch('auth/logout.php');
  window.location.reload();
}
document.getElementById('logoutBtn')?.addEventListener('click', doLogout);

// Profile Panel
const profileBtn = document.getElementById('profileBtn');
const profilePanel = document.getElementById('profilePanel');
const closeProfile = document.getElementById('closeProfile');
profileBtn.addEventListener('click', () => profilePanel.classList.toggle('open'));
closeProfile.addEventListener('click', () => profilePanel.classList.remove('open'));

// Chat Engine
async function loadMessages() {
  if (!CURRENT_USER_ID) return;
  const res = await fetch(`api/chat.php?since_id=${lastMsgId}`);
  const data = await res.json();
  if (data.success) {
    if (data.pinned_messages && data.pinned_messages.length > 0) {
      document.getElementById('pinnedBanner').hidden = false;
      document.getElementById('pinnedText').textContent = esc(data.pinned_messages[0].message);
    } else {
      document.getElementById('pinnedBanner').hidden = true;
    }

    if (data.messages.length > 0) {
      const msgContainer = document.getElementById('messages');
      data.messages.forEach(m => {
        if (m.id > lastMsgId) lastMsgId = m.id;

        const isMe = m.user_id === CURRENT_USER_ID;
        const row = document.createElement('div');
        row.className = `msg-row ${isMe ? 'me' : 'them'}`;

        let attachmentMarkup = '';
        if (m.attachment) {
          attachmentMarkup = `<div style="margin-top:6px;"><img src="${esc(m.attachment.file_path)}" style="max-width:100%;max-height:200px;border-radius:10px;"></div>`;
        }

        let replyMarkup = '';
        if (m.reply_to) {
          replyMarkup = `<div class="reply-preview"><strong>${esc(m.reply_to.user_name)}:</strong> ${esc(m.reply_to.snippet)}</div>`;
        }

        let reactionsMarkup = '';
        if (m.reactions && m.reactions.length > 0) {
          reactionsMarkup = '<div class="msg-reactions">';
          m.reactions.forEach(r => {
            reactionsMarkup += `<button class="reaction-pill ${r.reacted ? 'reacted' : ''}" onclick="toggleReaction(${m.id}, '${esc(r.emoji)}')">${esc(r.emoji)} ${r.count}</button>`;
          });
          reactionsMarkup += '</div>';
        }

        let actionsMarkup = `
          <div class="msg-actions">
            <button onclick="setReply(${m.id}, '${esc(m.display_name)}')">Reply</button>
            <button onclick="toggleReaction(${m.id}, '👍')">👍</button>
            <button onclick="toggleReaction(${m.id}, '❤️')">❤️</button>
            <button onclick="toggleReaction(${m.id}, '😂')">😂</button>
        `;
        if (isMe || IS_ADMIN) {
          actionsMarkup += `<button onclick="editMessage(${m.id}, '${esc(m.message)}')">Edit</button>`;
          actionsMarkup += `<button onclick="deleteMessage(${m.id})">Delete</button>`;
        }
        actionsMarkup += '</div>';

        row.innerHTML = `
          <div class="avatar xs" style="--h:${m.accent_hue}">${esc(m.display_name.charAt(0))}</div>
          <div class="msg-col">
            ${!isMe ? `<div class="sender">${esc(m.display_name)}</div>` : ''}
            <div class="bubble">
              ${replyMarkup}
              <p>${esc(m.message)}</p>
              ${attachmentMarkup}
              <span class="msg-time">${esc(m.created_at.substr(11, 5))}${m.is_edited ? ' (edited)' : ''}</span>
            </div>
            ${reactionsMarkup}
            ${actionsMarkup}
          </div>
        `;
        msgContainer.appendChild(row);
      });
      msgContainer.scrollTop = msgContainer.scrollHeight;
    }
  }
}

function setReply(msgId, userName) {
  activeReplyId = msgId;
  document.getElementById('activeReplyBar').hidden = false;
  document.getElementById('replyTargetUser').textContent = userName;
}

function cancelReply() {
  activeReplyId = null;
  document.getElementById('activeReplyBar').hidden = true;
}

async function editMessage(msgId, oldMsg) {
  const newText = prompt('Edit message:', oldMsg);
  if (!newText || newText === oldMsg) return;
  await fetch('api/chat.php', {
    method: 'PUT',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      csrf_token: CSRF_TOKEN,
      message_id: msgId,
      message: newText
    })
  });
  lastMsgId = 0;
  document.getElementById('messages').innerHTML = '';
  loadMessages();
}

async function deleteMessage(msgId) {
  if (!confirm('Delete this message?')) return;
  await fetch('api/chat.php', {
    method: 'DELETE',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      csrf_token: CSRF_TOKEN,
      message_id: msgId
    })
  });
  lastMsgId = 0;
  document.getElementById('messages').innerHTML = '';
  loadMessages();
}

async function toggleReaction(msgId, emoji) {
  await fetch('api/reactions.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      csrf_token: CSRF_TOKEN,
      message_id: msgId,
      emoji: emoji
    })
  });
  lastMsgId = 0;
  document.getElementById('messages').innerHTML = '';
  loadMessages();
}

// Search Feature Integration
const searchBtn = document.getElementById('searchBtn');
const searchInput = document.getElementById('searchInput');
const searchBox = document.getElementById('searchBox');
const searchResults = document.getElementById('searchResults');

searchBtn?.addEventListener('click', () => searchBox.classList.toggle('open'));

searchInput?.addEventListener('input', async () => {
  const q = searchInput.value.trim();
  if (!q) {
    searchResults.hidden = true;
    return;
  }
  const res = await fetch(`api/search.php?q=${encodeURIComponent(q)}`);
  const data = await res.json();
  if (data.success) {
    searchResults.hidden = false;
    let html = '';
    if (data.messages.length > 0) {
      html += '<div style="font-size:10px;font-weight:700;color:var(--text-dim);margin-bottom:6px;">MESSAGES</div>';
      data.messages.forEach(m => {
        html += `<div style="padding:6px;border-bottom:1px solid rgba(163,177,198,.2);font-size:12px;">
          <strong>${esc(m.display_name)}:</strong> ${esc(m.message)}
        </div>`;
      });
    } else {
      html = '<div style="padding:8px;font-size:12px;color:var(--text-dim);">No matches found</div>';
    }
    searchResults.innerHTML = html;
  }
});

// Sidebar Filter
document.getElementById('sideSearch')?.addEventListener('input', (e) => {
  const term = e.target.value.toLowerCase();
  document.querySelectorAll('#memberList li').forEach(li => {
    const text = li.textContent.toLowerCase();
    li.style.display = text.includes(term) ? '' : 'none';
  });
});

// Send Message
const composer = document.getElementById('composer');
const input = document.getElementById('input');
composer.addEventListener('submit', async (e) => {
  e.preventDefault();
  const text = input.value.trim();
  if (!text) return;

  const payload = {
    csrf_token: CSRF_TOKEN,
    message: text
  };
  if (activeReplyId) {
    payload.reply_to_id = activeReplyId;
  }

  const res = await fetch('api/chat.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if (data.success) {
    input.value = '';
    cancelReply();
    loadMessages();
  }
});

// Presence & Typing Polling
async function pollPresence() {
  if (!CURRENT_USER_ID) return;
  const isTyping = input.value.length > 0;
  const res = await fetch('api/presence.php');
  const data = await res.json();
  if (data.success) {
    const memberList = document.getElementById('memberList');
    document.getElementById('memberCount').textContent = data.users.length;
    memberList.innerHTML = '';
    data.users.forEach(u => {
      const li = document.createElement('li');
      li.innerHTML = `
        <div class="conv">
          <div class="avatar sm" style="--h:${u.accent_hue}">${esc(u.display_name.charAt(0))}<i class="status-dot ${u.presence_status === 'online' ? 'online' : ''}"></i></div>
          <div class="conv-body">
            <span class="conv-name">${esc(u.display_name)}</span>
            <span class="conv-last">${esc(u.bio_status || 'Member')}</span>
          </div>
        </div>
      `;
      memberList.appendChild(li);
    });

    const typingEl = document.getElementById('typingIndicator');
    if (data.typing_users && data.typing_users.length > 0) {
      const names = data.typing_users.map(u => esc(u.display_name)).join(', ');
      typingEl.textContent = `${names} is typing...`;
    } else {
      typingEl.textContent = '';
    }
  }

  fetch('api/presence.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      csrf_token: CSRF_TOKEN,
      status: 'online',
      is_typing: isTyping
    })
  });
}

// Attach Media
const attachBtn = document.getElementById('attachBtn');
attachBtn.addEventListener('click', () => {
  const fileInput = document.createElement('input');
  fileInput.type = 'file';
  fileInput.accept = 'image/*';
  fileInput.onchange = async () => {
    if (!fileInput.files.length) return;
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('csrf_token', CSRF_TOKEN);

    const res = await fetch('api/upload.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      await fetch('api/chat.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          csrf_token: CSRF_TOKEN,
          message: '[Uploaded Media]',
          attachment_id: data.attachment.id
        })
      });
      loadMessages();
    } else {
      alert(data.error || 'Upload failed');
    }
  };
  fileInput.click();
});

// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('sw.js').catch(() => {});
}

// Polling intervals
setInterval(loadMessages, 2500);
setInterval(pollPresence, 4500);
loadMessages();
pollPresence();
</script>
</body>
</html>
