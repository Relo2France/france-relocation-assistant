/**
 * settings.gradle.kts
 *
 * Gradle settings for MyTravelStatus Android app.
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

pluginManagement {
    repositories {
        google()
        mavenCentral()
        gradlePluginPortal()
    }
}

dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS)
    repositories {
        google()
        mavenCentral()
    }
}

rootProject.name = "MyTravelStatus"
include(":app")
