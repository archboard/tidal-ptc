import { ref } from 'vue'

export default {
  state: ref(),

  isDark () {
    return this.state.value === 'dark'
  }
}
