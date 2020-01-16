<template>
    <div class="trade flex">
        <div class="flex flex-col items-center bg-white h-screen shadow-inner sportstab py-10">
            <a href="#" class="mb-6 text-3xl"><i class="fas fa-futbol text-gray-500 hover:text-orange-500"></i></a>
            <a href="#" class="mb-6 text-3xl"><i class="fas fa-basketball-ball text-gray-500 hover:text-orange-500"></i></a>
            <a href="#" class="mb-6 text-3xl"><i class="fas fa-baseball-ball text-gray-500 hover:text-orange-500"></i></a>
            <a href="#" class="mb-6 text-3xl"><i class="fas fa-bowling-ball text-gray-500 hover:text-orange-500"></i></i></a>
            <a href="#" class="mb-6 text-3xl"><i class="fas fa-golf-ball text-gray-500 hover:text-orange-500"></i></a>
            <a href="#" class="mb-6 text-3xl"><i class="fas fa-volleyball-ball text-gray-500 hover:text-orange-500"></i></a>
            <a href="#" class="mb-6 text-3xl"><i class="fas fa-football-ball text-gray-500 hover:text-orange-500"></i></a>
        </div>

        <div class="flex flex-col w-2/12 h-screen p-8">
          <div class="flex justify-around">
            <a href="#" class="text-gray-700 text-sm uppercase font-bold p-5" v-for="(gameSchedMode, index) in gameSchedModes">{{gameSchedMode}}</a>
          </div>

          <div class="flex flex-col pt-2 text-center">
            <!-- Static at first, the actual league datas should be child components -->
            <a href="#" class="text-gray-700 text-sm capitalize font-bold p-5 w-full">Austria 2 Liga</a>
            <a href="#" class="text-gray-700 text-sm capitalize font-bold p-5 w-full bg-white rounded-lg shadow-lg">Belgium First Division A</a>
            <a href="#" class="text-gray-700 text-sm capitalize font-bold p-5 w-full">Belgium First Division B</a>
            <a href="#" class="text-gray-700 text-sm capitalize font-bold p-5 w-full">Denmark Super League</a>
            <a href="#" class="text-gray-700 text-sm capitalize font-bold p-5 w-full">Indian Super League</a>
            <a href="#" class="text-gray-700 text-sm capitalize font-bold p-5 w-full">Major Soccer League</a>
          </div>
        </div>

        <div class="w-full h-screen">
          <!-- League and games data here -->
        </div>
    </div>
</template>

<script>
export default {
  data() {
    return {
      gameSchedModes:['Live', 'Today', 'Early']
    }
  }
}
</script>

<style lang="scss">
  .sportstab {
    width:65px;
  }
</style>
