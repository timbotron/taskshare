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

  var boardUi = { showChrome: false, showSettings: false, error: null }

  state.lists.forEach(initListUi)
  function initListUi (list) {
    list._ui = { menu: false, editingName: false, nameValue: list.title, adding: false, addValue: '', editing: false }
    list.tasks.forEach(function (t) { t._editValue = t.text })
  }

  // XHR helper. Mithril redraws automatically when the promise settles. On error
  // it raises a quiet inline banner (boardUi.error) and rethrows so the caller can
  // revert its optimistic change (CODE-134); the API stays authoritative.
  var errorTimer = null
  function showError (err) {
    boardUi.error = (err && err.response && err.response.error) || 'Something went wrong.'
    clearTimeout(errorTimer)
    errorTimer = setTimeout(function () { boardUi.error = null; m.redraw() }, 4000)
  }
  function api (method, url, body) {
    return m.request({ method: method, url: url, body: body }).catch(function (err) {
      showError(err)
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
    var i = state.lists.indexOf(list)
    if (i < 0) return
    state.lists.splice(i, 1) // optimistic
    api('DELETE', base + '/lists/' + list.id).catch(function () {
      state.lists.splice(i, 0, list) // restore at its original spot
    })
  }
  function toggleEditTasks (list) {
    list._ui.editing = !list._ui.editing
    if (list._ui.editing) list.tasks.forEach(function (t) { t._editValue = t.text })
  }
  function clearCompleted (list) {
    // No confirm — it's a frequent action and only removes already-completed tasks.
    if (!hasCompleted(list)) return
    var prev = list.tasks
    list.tasks = prev.filter(function (t) { return !t.completed }) // optimistic
    api('DELETE', base + '/lists/' + list.id + '/completed').catch(function () {
      list.tasks = prev // restore the pre-clear set on failure
    })
  }
  function savePermissions () {
    return api('PUT', base + '/permissions', {
      allow_complete: state.permissions.allow_complete,
      allow_clear_completed: state.permissions.allow_clear_completed,
      allow_create_lists: state.permissions.allow_create_lists,
      allow_delete_lists: state.permissions.allow_delete_lists,
    })
  }

  // --- task actions ---
  // Temp ids for optimistic inserts are negative so they never collide with real
  // (positive) server ids; reconciled to the real id when the POST returns.
  var tempIdSeq = -1
  function addTask (list) {
    var text = (list._ui.addValue || '').trim()
    if (!text) return
    var task = { id: tempIdSeq--, text: text, completed: 0, position: list.tasks.length, _editValue: text, _pending: true }
    list.tasks.push(task) // optimistic — shows immediately
    list._ui.addValue = '' // keep the input open for the next one
    api('POST', base + '/lists/' + list.id + '/tasks', { text: text }).then(function (saved) {
      task.id = saved.id
      task.position = saved.position
      task._pending = false // now safe for complete/edit/reorder
    }).catch(function () {
      var i = list.tasks.indexOf(task)
      if (i >= 0) list.tasks.splice(i, 1) // revert the insert
    })
  }
  function saveTaskText (task) {
    var text = (task._editValue || '').trim()
    if (!text || task._pending || text === task.text) return // nothing to save
    var prev = task.text
    task.text = text // optimistic
    api('PUT', base + '/tasks/' + task.id, { text: text }).catch(function () {
      task.text = prev
      task._editValue = prev // revert
    })
  }
  // Set (not blindly toggle) the completed flag; click a struck task to un-strike.
  function toggleComplete (task) {
    if (task._pending) return // wait for its real id before mutating it
    var prev = task.completed
    var next = prev ? 0 : 1
    task.completed = next // optimistic
    api('PUT', base + '/tasks/' + task.id + '/complete', { completed: next }).catch(function () {
      task.completed = prev // revert
    })
  }

  // --- drag-to-reorder (edit mode only, owner) ---
  var dragTaskId = null
  // Move dragged task relative to target, dropping before/after by cursor half.
  function moveTask (list, dragId, targetId, after) {
    if (dragId == null || dragId === targetId) return
    var tasks = list.tasks
    var from = tasks.findIndex(function (t) { return t.id === dragId })
    if (from < 0) return
    var moved = tasks.splice(from, 1)[0]
    var to = tasks.findIndex(function (t) { return t.id === targetId })
    if (to < 0) { tasks.splice(from, 0, moved); return } // target gone: undo
    tasks.splice(after ? to + 1 : to, 0, moved)
  }
  function persistTaskOrder (list, prev) {
    api('PUT', base + '/lists/' + list.id + '/tasks/reorder', {
      order: list.tasks.map(function (t) { return t.id }),
    }).then(function () {
      list.tasks.forEach(function (t, i) { t.position = i })
    }).catch(function () {
      list.tasks = prev // restore the pre-drag order
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
    }, parts.map(function (p) {
      if (typeof p === 'string') return m('path', { d: p })
      return m(p.cx != null ? 'circle' : 'rect', p)
    }))
  }
  var ICON = {
    plus: ['M5 12h14', 'M12 5v14'],
    pencil: ['M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z', 'm15 5 4 4'],
    editTasks: [{ x: 3, y: 4, width: 6, height: 6, rx: 1 }, 'M13 5h8', 'M13 12h8', 'M13 19h8', 'm3 17 2 2 4-4'],
    clear: ['m16 22-1-4', 'M19 14a1 1 0 0 0 1-1v-1a2 2 0 0 0-2-2h-3a1 1 0 0 1-1-1V4a2 2 0 0 0-4 0v5a1 1 0 0 1-1 1H6a2 2 0 0 0-2 2v1a1 1 0 0 0 1 1', 'M19 14H5l-1.973 6.767A1 1 0 0 0 4 22h16a1 1 0 0 0 .973-1.233z', 'm8 22 1-4'],
    trash: ['M3 6h18', 'M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6', 'M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2', 'M10 11v6', 'M14 11v6'],
    gear: ['M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z', { cx: 12, cy: 12, r: 3 }],
    grip: [
      { cx: 9, cy: 6, r: 1.4, fill: 'currentColor' }, { cx: 9, cy: 12, r: 1.4, fill: 'currentColor' }, { cx: 9, cy: 18, r: 1.4, fill: 'currentColor' },
      { cx: 15, cy: 6, r: 1.4, fill: 'currentColor' }, { cx: 15, cy: 12, r: 1.4, fill: 'currentColor' }, { cx: 15, cy: 18, r: 1.4, fill: 'currentColor' },
    ],
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
      var editing = canEditTask && list._ui.editing

      // Left check button: toggles complete. Interactive only for those allowed;
      // otherwise a static indicator so viewers still see completion state.
      var checkAttrs = { class: 'check-btn' + (checked ? ' is-checked' : '') }
      var check = canComplete
        ? m('button', Object.assign({ title: 'Complete / un-complete', onclick: function () { toggleComplete(task) } }, checkAttrs), '✓')
        : m('span', checkAttrs, '✓')

      // Edit mode (owner, entered from the list options): text becomes an input.
      var body = editing
        ? m('input', {
            class: 'field text-sm',
            value: task._editValue,
            oninput: function (e) { task._editValue = e.target.value },
            onkeyup: function (e) { if (e.key === 'Enter') saveTaskText(task) },
            onblur: function () { saveTaskText(task) },
          })
        : m('span', { class: 'task-text flex-1' + (checked ? ' is-done' : '') }, task.text)

      var children = [check, body]
      var rowAttrs = { class: 'task-row' + ((dragTaskId === task.id || task._pending) ? ' opacity-50' : '') }

      // Edit mode adds a drag handle; the row is a drop target. Only the handle
      // is draggable so the text input stays freely editable (CODE-105).
      if (editing) {
        children.unshift(m('span', {
          class: 'drag-handle shrink-0 cursor-grab text-muted',
          title: 'Drag to reorder',
          draggable: true,
          ondragstart: function (e) {
            dragTaskId = task.id
            e.dataTransfer.effectAllowed = 'move'
            try { e.dataTransfer.setData('text/plain', String(task.id)) } catch (err) {}
          },
          ondragend: function () { dragTaskId = null },
        }, iconSvg(ICON.grip)))
        rowAttrs.ondragover = function (e) { e.preventDefault(); e.redraw = false } // allow drop, no redraw storm
        rowAttrs.ondrop = function (e) {
          e.preventDefault()
          var rect = e.currentTarget.getBoundingClientRect()
          var prev = list.tasks.slice() // snapshot for rollback on a failed save
          moveTask(list, dragTaskId, task.id, (e.clientY - rect.top) > rect.height / 2)
          persistTaskOrder(list, prev)
          dragTaskId = null
        }
      }

      return m('li', rowAttrs, children)
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
      return m('div', { class: 'mb-2 flex flex-wrap gap-4' }, items)
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
              class: 'options-btn' + (list._ui.menu ? ' is-open' : ''),
              title: 'List options',
              'aria-label': 'List options',
              onclick: function () { list._ui.menu = !list._ui.menu },
            }, iconSvg(ICON.gear))
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
              class: 'perm-checkbox',
              type: 'checkbox',
              checked: state.permissions[row[0]],
              onchange: function (e) {
                var key = row[0]
                var prev = state.permissions[key]
                state.permissions[key] = e.target.checked // optimistic
                savePermissions().catch(function () { state.permissions[key] = prev }) // revert the toggle
              },
            }),
            row[1],
          ])
        }),
      ])
    },
  }

  var BoardApp = {
    view: function () {
      var hasChrome = canCreateList || isOwner
      return m('div', [
        boardUi.error
          ? m('div', { class: 'mb-4 border border-red-500 px-3 py-2 text-sm text-red-600', role: 'alert' }, boardUi.error)
          : null,
        // Header: board title on the left; owner badge + options gear on the right.
        m('div', { class: 'mb-6 flex items-center justify-between gap-2' }, [
          m('h1', { class: 'text-2xl font-semibold' }, state.board.title),
          m('div', { class: 'flex items-center gap-3' }, [
            isOwner ? m('span', { class: 'text-sm text-gray-500' }, 'You own this board') : null,
            hasChrome
              ? m('button', {
                  class: 'options-btn' + (boardUi.showChrome ? ' is-open' : ''),
                  title: 'Board options',
                  'aria-label': 'Board options',
                  onclick: function () { boardUi.showChrome = !boardUi.showChrome },
                }, iconSvg(ICON.gear))
              : null,
          ]),
        ]),
        hasChrome && boardUi.showChrome
          ? m('div', { class: 'mb-4 flex justify-end gap-2' }, [
              canCreateList ? m('button', { class: 'btn', onclick: addList }, '+ New list') : null,
              isOwner
                ? m('button', { class: 'menu-btn', onclick: function () { boardUi.showSettings = !boardUi.showSettings } }, boardUi.showSettings ? 'Hide settings' : 'Settings')
                : null,
            ])
          : null,
        isOwner && boardUi.showChrome && boardUi.showSettings ? m(Settings) : null,
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
