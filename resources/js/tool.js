import Tool from './components/Tool.vue'

Nova.booting((app) => {
  app.component('commenter', Tool)
})
