(function () {
  var root = document.documentElement;
  var toggle = document.querySelector('[data-theme-toggle]');
  if (toggle) toggle.addEventListener('click', function () {
    var dark = root.dataset.theme ? root.dataset.theme === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
    var next = dark ? 'light' : 'dark';
    root.dataset.theme = next;
    try { localStorage.setItem('alba-theme', next); } catch (e) {}
  });

  var db = document.querySelector('[data-db-form]');
  if (db) {
    var driver = db.querySelector('[data-driver]');
    var ports = { mysql: '3306', pgsql: '5432' };
    var sync = function (initial) {
      var sqlite = driver.value === 'sqlite';
      db.querySelectorAll('[data-net]').forEach(function (el) { el.hidden = sqlite; });
      db.querySelector('[data-db-label]').textContent = sqlite ? 'Database file' : 'Database name';
      if (initial) return;
      var name = db.querySelector('[name=database]');
      if (sqlite) name.value = db.dataset.sqliteDefault;
      else if (name.value === db.dataset.sqliteDefault) name.value = '';
      if (ports[driver.value]) db.querySelector('[name=port]').value = ports[driver.value];
    };
    driver.addEventListener('change', function () { sync(false); });
    sync(true);
  }

  var tasks = document.querySelector('[data-tasks]');
  if (tasks) {
    var button = tasks.querySelector('[data-run]');
    var token = document.body.dataset.token;
    button.addEventListener('click', async function (event) {
      event.preventDefault();
      button.disabled = true;
      var items = tasks.querySelectorAll('[data-task]');
      for (var i = 0; i < items.length; i++) {
        var li = items[i], log = li.querySelector('.alba-log');
        li.dataset.state = 'running';
        var ok = false, text = '';
        try {
          var res = await fetch(tasks.dataset.runUrl + '/' + li.dataset.task, { method: 'POST', headers: { 'X-CSRF-Token': token } });
          var json = await res.json();
          ok = json.ok; text = json.log;
        } catch (e) { text = 'Request failed: ' + e.message; }
        li.dataset.state = ok ? 'ok' : 'bad';
        log.textContent = text; log.hidden = !text;
        if (!ok) { button.disabled = false; button.textContent = 'Retry'; return; }
      }
      tasks.submit();
    });
  }
})();
