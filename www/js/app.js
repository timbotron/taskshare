;(function () {
  'use strict'

  // --- Theme toggle (every page) ---
  var themeBtn = document.getElementById('theme-toggle')
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      var el = document.documentElement
      var current = el.getAttribute('data-theme') ||
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
      var next = current === 'dark' ? 'light' : 'dark'
      el.setAttribute('data-theme', next)
      if (el.getAttribute('data-auth')) {
        m.request({ method: 'POST', url: '/theme', body: { theme: next } }) // logged-in: persist server-side
      } else {
        try { localStorage.setItem('ts-theme', next) } catch (e) {} // anonymous: localStorage
      }
    })
  }

  // --- Home demo (CODE-77 scaffold): proves the no-build frontend is wired ---
  var demoRoot = document.getElementById('ts-demo')
  if (demoRoot) {
    var count = 0
    var Demo = {
      view: function () {
        return m('div', { class: 'space-y-3' }, [
          m('p', { class: 'text-sm text-gray-500' }, 'Mithril is wired. Clicks: ' + count),
          m('button', { class: 'btn', onclick: function () { count++ } }, 'Click me'),
        ])
      },
    }
    m.mount(demoRoot, Demo)
  }

  // --- Board view (CODE-81 hydrate + CODE-82 CRUD + 83 complete + 84 clear + 86 perms) ---
  var boardRoot = document.getElementById('board-app')
  if (!boardRoot || !window.__TASKSHARE__) return

  var state = window.__TASKSHARE__
  var slug = state.board.slug
  var isOwner = state.is_owner
  var base = '/b/' + slug

  // Per-action capabilities. The owner may do anything; an anonymous link-holder
  // may do only what the board permits. These MIRROR the server (board_for_action);
  // the API is authoritative — hiding a control is convenience, not security.
  // Adding/editing tasks and renaming lists are owner-only (not shareable perms).
  var p = state.permissions
  var canAddTask = isOwner
  var canEditTask = isOwner
  var canRenameList = isOwner
  var canCreateList = isOwner || p.allow_create_lists
  var canDeleteList = isOwner || p.allow_delete_lists
  var canComplete = isOwner || p.allow_complete
  var canClearCompleted = isOwner || p.allow_clear_completed

  var boardUi = { showSettings: false }

  state.lists.forEach(initListUi)
  function initListUi (list) {
    list._ui = { menu: false, editingName: false, nameValue: list.title, adding: false, addValue: '', editing: false }
    list.tasks.forEach(function (t) { t._editValue = t.text })
  }

  // XHR helper. Mithril redraws automatically when the promise settles.
  function api (method, url, body) {
    return m.request({ method: method, url: url, body: body }).catch(function (err) {
      window.alert((err && err.response && err.response.error) || 'Something went wrong.')
      throw err
    })
  }

  // --- list actions ---
  function addList () {
    api('POST', base + '/lists', {}).then(function (list) {
      initListUi(list)
      if (canRenameList) { list._ui.editingName = true; list._ui.menu = true } // owner names it inline
      state.lists.push(list)
    })
  }
  function saveListName (list) {
    var title = (list._ui.nameValue || '').trim()
    if (!title) return
    api('PUT', base + '/lists/' + list.id, { title: title }).then(function () {
      list.title = title
      list._ui.editingName = false
    })
  }
  function deleteList (list) {
    if (!window.confirm('Delete this list and all its tasks?')) return
    api('DELETE', base + '/lists/' + list.id).then(function () {
      var i = state.lists.indexOf(list)
      if (i >= 0) state.lists.splice(i, 1)
    })
  }
  function toggleEditTasks (list) {
    list._ui.editing = !list._ui.editing
    if (list._ui.editing) list.tasks.forEach(function (t) { t._editValue = t.text })
  }
  function clearCompleted (list) {
    if (!window.confirm('Clear all completed tasks from this list?')) return
    api('DELETE', base + '/lists/' + list.id + '/completed').then(function () {
      list.tasks = list.tasks.filter(function (t) { return !t.completed })
    })
  }
  function savePermissions () {
    api('PUT', base + '/permissions', {
      allow_complete: state.permissions.allow_complete,
      allow_clear_completed: state.permissions.allow_clear_completed,
      allow_create_lists: state.permissions.allow_create_lists,
      allow_delete_lists: state.permissions.allow_delete_lists,
    })
  }

  // --- task actions ---
  function addTask (list) {
    var text = (list._ui.addValue || '').trim()
    if (!text) return
    api('POST', base + '/lists/' + list.id + '/tasks', { text: text }).then(function (task) {
      task._editValue = task.text
      list.tasks.push(task)
      list._ui.addValue = '' // keep the input open for the next one
    })
  }
  function saveTaskText (task) {
    var text = (task._editValue || '').trim()
    if (!text) return
    api('PUT', base + '/tasks/' + task.id, { text: text }).then(function () { task.text = text })
  }
  // Set (not blindly toggle) the completed flag; click a struck task to un-strike.
  function toggleComplete (task) {
    var next = task.completed ? 0 : 1
    api('PUT', base + '/tasks/' + task.id + '/complete', { completed: next }).then(function () {
      task.completed = next
    })
  }

  function focusOnCreate (vnode) { vnode.dom.focus() }

  function hasCompleted (list) { return list.tasks.some(function (t) { return t.completed }) }
  // The ▾ options control shows when there's anything to offer for this caller.
  function canOpenOptions (list) {
    return canAddTask || canRenameList || canEditTask || canDeleteList ||
      (canClearCompleted && hasCompleted(list))
  }

  // --- inline SVG icons (Lucide, ISC), drawn with currentColor so they theme ---
  function iconSvg (parts, size) {
    return m('svg', {
      width: size || 18, height: size || 18, viewBox: '0 0 24 24', fill: 'none',
      stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round',
      'stroke-linejoin': 'round', 'aria-hidden': 'true',
    }, parts.map(function (p) { return typeof p === 'string' ? m('path', { d: p }) : m('rect', p) }))
  }
  var ICON = {
    plus: ['M5 12h14', 'M12 5v14'],
    pencil: ['M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z', 'm15 5 4 4'],
    editTasks: [{ x: 3, y: 4, width: 6, height: 6, rx: 1 }, 'M13 5h8', 'M13 12h8', 'M13 19h8', 'm3 17 2 2 4-4'],
    clear: ['m16 22-1-4', 'M19 14a1 1 0 0 0 1-1v-1a2 2 0 0 0-2-2h-3a1 1 0 0 1-1-1V4a2 2 0 0 0-4 0v5a1 1 0 0 1-1 1H6a2 2 0 0 0-2 2v1a1 1 0 0 0 1 1', 'M19 14H5l-1.973 6.767A1 1 0 0 0 4 22h16a1 1 0 0 0 .973-1.233z', 'm8 22 1-4'],
    trash: ['M3 6h18', 'M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6', 'M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2', 'M10 11v6', 'M14 11v6'],
  }
  function iconBtn (parts, label, onclick, extraClass) {
    return m('button', {
      class: 'icon-btn' + (extraClass ? ' ' + extraClass : ''),
      title: label, 'aria-label': label, onclick: onclick,
    }, iconSvg(parts))
  }

  // --- components ---
  var TaskRow = {
    view: function (vnode) {
      var list = vnode.attrs.list
      var task = vnode.attrs.task
      var checked = !!task.completed

      // Left check button: toggles complete. Interactive only for those allowed;
      // otherwise a static indicator so viewers still see completion state.
      var checkAttrs = { class: 'check-btn' + (checked ? ' is-checked' : '') }
      var check = canComplete
        ? m('button', Object.assign({ title: 'Complete / un-complete', onclick: function () { toggleComplete(task) } }, checkAttrs), '✓')
        : m('span', checkAttrs, '✓')

      // Edit mode (owner, entered from the list options): text becomes an input.
      var body = (canEditTask && list._ui.editing)
        ? m('input', {
            class: 'field text-sm',
            value: task._editValue,
            oninput: function (e) { task._editValue = e.target.value },
            onkeyup: function (e) { if (e.key === 'Enter') saveTaskText(task) },
            onblur: function () { saveTaskText(task) },
          })
        : m('span', { class: 'task-text flex-1' + (checked ? ' is-done' : '') }, task.text)

      return m('li', { class: 'task-row' }, [check, body])
    },
  }

  // Icon-only options (CODE-92). Edit name lives next to the title (below), not here.
  var ListMenu = {
    view: function (vnode) {
      var list = vnode.attrs.list
      var items = []
      if (canAddTask) items.push(iconBtn(ICON.plus, 'Add task', function () { list._ui.adding = true }))
      if (canEditTask) items.push(iconBtn(ICON.editTasks, list._ui.editing ? 'Done editing' : 'Edit tasks', function () { toggleEditTasks(list) }, list._ui.editing ? 'is-active' : ''))
      if (canClearCompleted && hasCompleted(list)) items.push(iconBtn(ICON.clear, 'Clear completed', function () { clearCompleted(list) }))
      if (canDeleteList) items.push(iconBtn(ICON.trash, 'Delete list', function () { deleteList(list) }, 'icon-danger'))
      return m('div', { class: 'mb-2 flex flex-wrap gap-1' }, items)
    },
  }

  var ListHeader = {
    view: function (vnode) {
      var list = vnode.attrs.list
      if (canRenameList && list._ui.editingName) {
        return m('div', { class: 'mb-2 flex gap-1' }, [
          m('input', {
            class: 'field font-semibold',
            value: list._ui.nameValue,
            oncreate: focusOnCreate,
            oninput: function (e) { list._ui.nameValue = e.target.value },
            onkeyup: function (e) { if (e.key === 'Enter') saveListName(list) },
          }),
          m('button', { class: 'menu-btn', onclick: function () { saveListName(list) } }, 'Save'),
        ])
      }
      return m('div', { class: 'mb-2 flex items-center justify-between gap-2' }, [
        m('div', { class: 'flex items-center gap-1.5' }, [
          m('h3', { class: 'font-semibold' }, list.title),
          // Edit-name pencil: next to the title, only while the accordion is open.
          (canRenameList && list._ui.menu)
            ? iconBtn(ICON.pencil, 'Edit name', function () { list._ui.editingName = true; list._ui.nameValue = list.title }, 'icon-inline')
            : null,
        ]),
        canOpenOptions(list)
          ? m('button', {
              class: 'options-btn',
              title: 'List options',
              'aria-label': 'List options',
              onclick: function () { list._ui.menu = !list._ui.menu },
            }, list._ui.menu ? '▴' : '▾')
          : null,
      ])
    },
  }

  var AddTask = {
    view: function (vnode) {
      var list = vnode.attrs.list
      return m('div', { class: 'mt-2 flex gap-1' }, [
        m('input', {
          class: 'field text-sm',
          placeholder: 'New task',
          value: list._ui.addValue,
          oncreate: focusOnCreate,
          oninput: function (e) { list._ui.addValue = e.target.value },
          onkeyup: function (e) { if (e.key === 'Enter') addTask(list) },
        }),
        m('button', { class: 'menu-btn', onclick: function () { addTask(list) } }, 'Add'),
        m('button', { class: 'menu-btn', onclick: function () { list._ui.adding = false; list._ui.addValue = '' } }, 'Done'),
      ])
    },
  }

  var ListCard = {
    view: function (vnode) {
      var list = vnode.attrs.list
      return m('div', { class: 'list-card' }, [
        m(ListHeader, { list: list }),
        canOpenOptions(list) && list._ui.menu ? m(ListMenu, { list: list }) : null,
        list.tasks.length
          ? m('ul', { class: 'space-y-1' }, list.tasks.map(function (t) {
              return m(TaskRow, { key: t.id, list: list, task: t })
            }))
          : m('p', { class: 'text-sm text-muted' }, 'No tasks yet.'),
        canAddTask && list._ui.adding ? m(AddTask, { list: list }) : null,
      ])
    },
  }

  var PERMISSION_LABELS = [
    ['allow_complete', 'Let anyone with the link complete items'],
    ['allow_clear_completed', 'Let them clear completed tasks'],
    ['allow_create_lists', 'Let them create lists'],
    ['allow_delete_lists', 'Let them delete lists'],
  ]
  var Settings = {
    view: function () {
      return m('div', { class: 'list-card mb-4 max-w-xl' }, [
        m('h2', { class: 'mb-1 font-semibold' }, 'Sharing permissions'),
        m('p', { class: 'mb-2 text-sm text-muted' }, 'Anyone with the link can view. Choose what they can also do:'),
        PERMISSION_LABELS.map(function (row) {
          return m('label', { class: 'flex items-center gap-2 py-1 text-sm' }, [
            m('input', {
              type: 'checkbox',
              checked: state.permissions[row[0]],
              onchange: function (e) { state.permissions[row[0]] = e.target.checked; savePermissions() },
            }),
            row[1],
          ])
        }),
      ])
    },
  }

  var BoardApp = {
    view: function () {
      return m('div', [
        (canCreateList || isOwner)
          ? m('div', { class: 'mb-4 flex gap-2' }, [
              canCreateList ? m('button', { class: 'btn', onclick: addList }, '+ New list') : null,
              isOwner
                ? m('button', { class: 'menu-btn', onclick: function () { boardUi.showSettings = !boardUi.showSettings } }, boardUi.showSettings ? 'Hide settings' : 'Settings')
                : null,
            ])
          : null,
        isOwner && boardUi.showSettings ? m(Settings) : null,
        state.lists.length
          ? m('div', { class: 'board-grid' }, state.lists.map(function (list) {
              return m(ListCard, { key: list.id, list: list })
            }))
          : m('p', { class: 'text-muted' }, canCreateList ? 'No lists yet. Add one to get started.' : 'This board has no lists yet.'),
      ])
    },
  }

  m.mount(boardRoot, BoardApp)
})()
