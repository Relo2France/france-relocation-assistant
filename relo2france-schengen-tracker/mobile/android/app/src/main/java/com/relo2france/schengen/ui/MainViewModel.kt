/**
 * MainViewModel.kt
 *
 * Main ViewModel managing app state and user interactions.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.ui

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.relo2france.schengen.data.*
import com.relo2france.schengen.network.ApiClient
import com.relo2france.schengen.util.SecureStorage
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch

class MainViewModel(application: Application) : AndroidViewModel(application) {

    private val repository = SchengenRepository.getInstance(application)
    private val apiClient = ApiClient.getInstance()

    // UI State
    private val _uiState = MutableStateFlow(MainUiState())
    val uiState: StateFlow<MainUiState> = _uiState.asStateFlow()

    // Collect repository state
    val trips: StateFlow<List<Trip>> = repository.trips
    val passportData: StateFlow<PassportControlData?> = repository.passportData
    val isOnline: StateFlow<Boolean> = repository.isOnline

    init {
        // Initialize
        viewModelScope.launch {
            checkAuthStatus()
            loadInitialData()
        }

        // Observe trips for pending count
        viewModelScope.launch {
            repository.trips.collect { trips ->
                val pendingCount = trips.count { it.syncStatus == SyncStatus.PENDING }
                _uiState.update { it.copy(pendingSyncCount = pendingCount) }
            }
        }
    }

    private fun checkAuthStatus() {
        val isLoggedIn = SecureStorage.isAuthenticated()
        _uiState.update {
            it.copy(
                isLoggedIn = isLoggedIn,
                userName = if (isLoggedIn) "User" else null // TODO: Get actual username
            )
        }
    }

    private suspend fun loadInitialData() {
        _uiState.update { it.copy(isLoading = true) }

        try {
            // Load app status
            apiClient.getAppStatus().onSuccess { status ->
                _uiState.update { it.copy(appStatus = status) }
            }

            // Load passport data
            repository.getPassportControlData(forceRefresh = true)

            // Load trips
            repository.getTrips(forceRefresh = true)

        } catch (e: Exception) {
            _uiState.update { it.copy(error = e.message) }
        } finally {
            _uiState.update { it.copy(isLoading = false) }
        }
    }

    // MARK: - User Actions

    fun refresh() {
        viewModelScope.launch {
            loadInitialData()
        }
    }

    fun syncNow() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            try {
                repository.fullSync()
            } catch (e: Exception) {
                _uiState.update { it.copy(error = e.message) }
            } finally {
                _uiState.update { it.copy(isLoading = false) }
            }
        }
    }

    fun login(username: String, password: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }

            apiClient.login(username, password)
                .onSuccess { response ->
                    SecureStorage.setAuthToken(response.token)
                    SecureStorage.save(SecureStorage.Keys.USER_ID, response.userId.toString())
                    _uiState.update {
                        it.copy(
                            isLoggedIn = true,
                            userName = username,
                            isLoading = false
                        )
                    }
                    loadInitialData()
                }
                .onFailure { error ->
                    _uiState.update {
                        it.copy(
                            error = error.message,
                            isLoading = false
                        )
                    }
                }
        }
    }

    fun logout() {
        viewModelScope.launch {
            apiClient.logout()
            SecureStorage.clearAuth()
            repository.clearAll()
            _uiState.update {
                MainUiState(isLoggedIn = false)
            }
        }
    }

    fun toggleLocationTracking(enabled: Boolean) {
        _uiState.update { it.copy(isLocationEnabled = enabled) }
        // TODO: Actually enable/disable location tracking via LocationScheduler
    }

    fun toggleNotifications(enabled: Boolean) {
        _uiState.update { it.copy(isNotificationsEnabled = enabled) }
        // TODO: Actually enable/disable notifications
    }

    fun clearLocalData() {
        repository.clearAll()
        _uiState.update { it.copy(pendingSyncCount = 0) }
    }

    fun addTrip(trip: Trip) {
        viewModelScope.launch {
            repository.insertTrip(trip)
        }
    }

    fun updateTrip(trip: Trip) {
        viewModelScope.launch {
            repository.updateTrip(trip)
        }
    }

    fun deleteTrip(trip: Trip) {
        viewModelScope.launch {
            repository.deleteTrip(trip)
        }
    }

    fun clearError() {
        _uiState.update { it.copy(error = null) }
    }

    fun setOnlineStatus(online: Boolean) {
        repository.setOnlineStatus(online)
    }
}

data class MainUiState(
    val isLoading: Boolean = false,
    val isLoggedIn: Boolean = false,
    val userName: String? = null,
    val appStatus: AppStatusResponse? = null,
    val isLocationEnabled: Boolean = true,
    val isNotificationsEnabled: Boolean = true,
    val pendingSyncCount: Int = 0,
    val error: String? = null
)
