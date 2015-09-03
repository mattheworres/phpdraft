class DraftIndexController extends BaseController
  @register 'DraftIndexController'
  @inject '$scope',
  '$rootScope',
  '$routeParams',
  'subscriptionKeys',
  'api',
  'workingModalService',
  'messageService',
  'draftService'

  initialize: ->
    @loadedCommishManagers = false
    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      if args.draft? and args.draft.setting_up == true
        @_loadSettingUpData(args.draft.draft_id, args)
      else if args.draft? and args.draft.in_progress == true
        @_loadInProgressData(args.draft.draft_id, args)
      else if args.draft? and args.draft.complete == true
        @$scope.selectedDraftRound = 1
        @$scope.pagerItemTally = @$rootScope.draft.draft_rounds * 10
        @_loadCompletedData(args.draft.draft_id, args)

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()
      @deregister_commish_managers()

    @deregister_commish_managers = @$rootScope.$on @subscriptionKeys.updateCommishManagers, (event, args) =>
      @_reloadEditableManagers()

    @$scope.$watch ( =>
      @$scope.selectedDraftRound
    ), =>
      if @$scope.draft != undefined and @$scope.draft.draft_id == @$routeParams.draft_id and @$scope.draft.complete and @$scope.draftValid and not @$scope.draftLocked
        @_loadCompletedData(@$routeParams.draft_id)

  _loadSettingUpData: (draft_id, args) =>
    managersSuccess = (data) =>
      @$scope.managersLoading = false
      @$scope.managers = data

    commishManagersSuccess = (data) =>
      @$scope.commishManagersLoading = false
      @$scope.editableManagers = data

    managersError = (response) =>
      @$scope.managersLoading = false
      @$scope.managersError = true
      @messageService.showError "Unable to load managers"

    @$scope.managersLoading = args.onPageLoad? and args.onPageLoad
    @$scope.commishManagersLoading = args.onPageLoad? and args.onPageLoad
    @$scope.managersError = false

    if @$scope.draftValid and not @$scope.draftLocked
      if not args.draft.commish_editable
        @api.Manager.getManagers({ draft_id: draft_id }, managersSuccess, managersError)
      if not @loadedCommishManagers and args.draft.commish_editable
        @loadedCommishManagers = true
        @_reloadEditableManagers()

  reorderManagers: (event, spliceIndex, originalIndex) =>
    manager = @$scope.editableManagers[originalIndex]
    @$scope.editableManagers.splice originalIndex, 1
    @$scope.editableManagers.splice spliceIndex, 0, manager
    
    @_saveManagerOrder(@$scope.editableManagers)

    return true;

  openAddManagerModal: ->
    @draftService.showAddManagersModal @$scope.draft.draft_id

  _saveManagerOrder: (managers) ->
    reorderSuccess = (data) =>
      @commishManagersLoading = false
      draft_order = 1
      for manager in managers
        manager.draft_order = draft_order
        draft_order++

    reorderError = (response) =>
      @commishManagersLoading = false
      @messageService.showError "Unable to reorder managers"

    @commishManagersLoading = true
    manager_ids = []
    draft_order = 1
    for manager in managers
      manager_ids.push manager.manager_id

    @api.Manager.reorder({ draft_id: @$scope.draft.draft_id, ordered_manager_ids: manager_ids }, reorderSuccess, reorderError)

  _reloadEditableManagers: =>
    commishManagersSuccess = (data) =>
      @$scope.commishManagersLoading = false
      @$scope.editableManagers = data

    managersError = (response) =>
      @$scope.managersLoading = false
      @$scope.managersError = true
      @messageService.showError "Unable to load managers"

    @api.Manager.commishGet({ draft_id: @$scope.draft.draft_id }, commishManagersSuccess, managersError)

  beforeSwipe: (event) =>
    event.preventDefault()

  _loadInProgressData: (draft_id, args) =>
    lastSuccess = (data) =>
      @$scope.lastLoading = false
      @$scope.lastFivePicks = data

    nextSuccess = (data) =>
      @$scope.nextLoading = false
      @$scope.nextFivePicks = data

    lastErrorHandler = (data) =>
      @$scope.lastLoading = false
      @$scope.lastError = true

    nextErrorHandler = (data) =>
      @$scope.nextLoading = false
      @$scope.nextError = true

    @$scope.lastLoading = args.onPageLoad? and args.onPageLoad
    @$scope.nextLoading = args.onPageLoad? and args.onPageLoad
    @$scope.lastError = false
    @$scope.nextError = false

    if @$scope.draftValid and not @$scope.draftLocked
      lastPromise = @api.Pick.getLast({ draft_id: draft_id, amount: 5 }, lastSuccess, lastErrorHandler)
      nextPromise = @api.Pick.getNext({ draft_id: draft_id, amount: 5 }, nextSuccess, nextErrorHandler)

  _loadCompletedData: (draft_id) =>
    roundSuccess = (data) =>
      @$scope.roundPicks = data
      @$scope.roundLoading = false

    errorHandler = (data) =>
      @messageService.showError "Unable to load picks"
      @$scope.roundError = true
      @$scope.roundLoading = false

    if @$scope.draft != undefined and @$scope.draftValid and not @$scope.draftLocked

      @$scope.roundError = false
      @$scope.roundLoading = true
      roundPromise = @api.Pick.getSelectedByRound({ draft_id: draft_id, round: @$scope.selectedDraftRound, sort_ascending: true }, roundSuccess, errorHandler)



