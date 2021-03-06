angular.module('phpdraft.pick').config(($routeProvider, $locationProvider) => {
  $locationProvider
    .html5Mode(true)
    .hashPrefix('!');

  $routeProvider.when('/commish/draft/:draft_id/picks/add', {
    template: '<phpd-add-pick></phpd-add-pick>',
  });

  $routeProvider.when('/commish/draft/:draft_id/picks/edit', {
    template: '<phpd-edit-pick></phpd-edit-pick>',
  });

  $routeProvider.when('/draft/:draft_id/picks/manager', {
    template: '<phpd-picks-by-manager></phpd-picks-by-manager>',
  });

  $routeProvider.when('/draft/:draft_id/picks/round', {
    template: '<phpd-picks-by-round-page></phpd-picks-by-round-page>',
  });

  $routeProvider.when('/draft/:draft_id/search', {
    template: '<phpd-search></phpd-search>',
  });
});
