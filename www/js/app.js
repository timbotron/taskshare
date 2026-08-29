;(function () {
  'use strict'

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

  // --- Board view (CODE-81 hydrate + CODE-82 list/task CRUD) ---
  var boardRoot = document.getElementById('board-app')
  if (!boardRoot || !window.__TASKSHARE__) return

  var state = window.__TASKSHARE__
  var slug = state.board.slug
  var isOwner = state.is_owner
  var base = '/b/' + slug

  state.lists.forEach(initListUi)
  function initListUi (list) {
    list._ui = { menu: false, editingName: false, nameValue: list.title, adding: false, addValue: '' }
    list.tasks.forEach(function (t) { t._editing = false; t._editValue = t.text })
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
      list._ui.editingName = true // drop straight into naming the new list
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

  // --- task actions ---
  function addTask (list) {
    var text = (list._ui.addValue || '').trim()
    if (!text) return
    api('POST', base + '/lists/' + list.id + '/tasks', { text: text }).then(function (task) {
      task._editing = false
      task._editValue = task.text
      list.tasks.push(task)
      list._ui.addValue = '' // keep the input open for the next one
    })
  }
  function saveTaskText (task) {
    var text = (task._editValue || '').trim()
    if (!text) return
    api('PUT', base + '/tasks/' + task.id, { text: text }).then(function () {
      task.text = text
      task._editing = false
    })
  }

  function focusOnCreate (vnode) { vnode.dom.focus() }

  // --- components ---
  var TaskRow = {
    view: function (vnode) {
      var list = vnode.attrs.list
      var task = vnode.attrs.task
      if (isOwner && task._editing) {
        return m('li', { class: 'task-row' }, [
          m('input', {
            class: 'w-full rounded border border-gray-300 px-2 py-1 text-sm',
            value: task._editValue,
            oncreate: focusOnCreate,
            oninput: function (e) { task._editValue = e.target.value },
            onkeyup: function (e) { if (e.key === 'Enter') saveTaskText(task) },
            onblur: function () { saveTaskText(task) },
          }),
        ])
      }
      return m('li', {
        class: 'task-row' + (task.completed ? ' is-done' : '') + (isOwner ? ' cursor-text' : ''),
        onclick: isOwner ? function () { task._editing = true; task._editValue = task.text } : undefined,
      }, task.text)
    },
  }

  var ListMenu = {
    view: function (vnode) {
      var list = vnode.attrs.list
      return m('div', { class: 'mb-2 flex gap-1' }, [
        m('button', { class: 'menu-btn', onclick: function () { list._ui.adding = true; list._ui.menu = false } }, 'Add task'),
        m('button', { class: 'menu-btn', onclick: function () { list._ui.editingName = true; list._ui.nameValue = list.title; list._ui.menu = false } }, 'Edit name'),
        m('button', { class: 'menu-btn text-red-700', onclick: function () { deleteList(list) } }, 'Delete'),
      ])
    },
  }

  var ListHeader = {
    view: function (vnode) {
      var list = vnode.attrs.list
      if (isOwner && list._ui.editingName) {
        return m('div', { class: 'mb-2 flex gap-1' }, [
          m('input', {
            class: 'w-full rounded border border-gray-300 px-2 py-1 font-semibold',
            value: list._ui.nameValue,
            oncreate: focusOnCreate,
            oninput: function (e) { list._ui.nameValue = e.target.value },
            onkeyup: function (e) { if (e.key === 'Enter') saveListName(list) },
          }),
          m('button', { class: 'menu-btn', onclick: function () { saveListName(list) } }, 'Save'),
        ])
      }
      return m('h3', {
        class: 'mb-2 font-semibold' + (isOwner ? ' cursor-pointer' : ''),
        onclick: isOwner ? function () { list._ui.menu = !list._ui.menu } : undefined,
        title: isOwner ? 'Click for list options' : undefined,
      }, list.title)
    },
  }

  var AddTask = {
    view: function (vnode) {
      var list = vnode.attrs.list
      return m('div', { class: 'mt-2 flex gap-1' }, [
        m('input', {
          class: 'w-full rounded border border-gray-300 px-2 py-1 text-sm',
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
        isOwner && list._ui.menu ? m(ListMenu, { list: list }) : null,
        list.tasks.length
          ? m('ul', { class: 'space-y-1' }, list.tasks.map(function (t) {
              return m(TaskRow, { key: t.id, list: list, task: t })
            }))
          : m('p', { class: 'text-sm text-gray-400' }, 'No tasks yet.'),
        isOwner && list._ui.adding ? m(AddTask, { list: list }) : null,
      ])
    },
  }

  var BoardApp = {
    view: function () {
      return m('div', [
        isOwner
          ? m('div', { class: 'mb-4' }, m('button', { class: 'btn', onclick: addList }, '+ New list'))
          : null,
        state.lists.length
          ? m('div', { class: 'board-grid' }, state.lists.map(function (list) {
              return m(ListCard, { key: list.id, list: list })
            }))
          : m('p', { class: 'text-gray-500' }, isOwner ? 'No lists yet. Add one to get started.' : 'This board has no lists yet.'),
      ])
    },
  }

  m.mount(boardRoot, BoardApp)
})()
