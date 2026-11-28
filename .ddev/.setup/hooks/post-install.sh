# Repo-owned install hook (sourced by the add-on after each instance's setup).
# The instance pushes to the local E2E Observer, whose project for this TYPO3
# version has a fixed token: `ddev e2e-reset` in the cms-observer repository
# creates them (its CONTRIBUTING.md lists them).
observer_api_url='https://e2e.cms-observer.ddev.site'
observer_token="e2e-typo3-${VERSION}"
if [ "${MODE:-composer}" = "classic" ]; then
    classic_configuration_set "EXTENSIONS/${EXTENSION_KEY}/apiUrl" "$observer_api_url"
    classic_configuration_set "EXTENSIONS/${EXTENSION_KEY}/token" "$observer_token"
else
    $TYPO3_BIN configuration:set "EXTENSIONS/${EXTENSION_KEY}/apiUrl" "$observer_api_url"
    $TYPO3_BIN configuration:set "EXTENSIONS/${EXTENSION_KEY}/token" "$observer_token"
fi
