# Testing - Vraj Chauhan & Damon Fernandez


# API Testing Documentation

## Movies

### GET /movies/

#### Expected
Should return json of all movies.

#### Actual Output(it was too big for thunderclient)
![ ](./image.png)
![alt text](image-1.png)



### GET /movies/{id}

#### Expected
Should return the movie data for a specific movie.

#### Actual Output
![alt text](image-2.png)

### GET /movies/{id}/rating

#### Expected
Should return the rating value for a specific movie.

#### Actual Output
![alt text](image-3.png)

## toWatchList

### GET /towatchlist/entries

#### Expected
Should return all entries on the user's toWatchList.

#### Actual Output
![alt text](image-4.png)
![alt text](image-5.png)

### POST /towatchlist/entries

#### Expected
Should validate and insert the new entry into the toWatchList.

#### Actual Output
![alt text](image-6.png)
![alt text](image-7.png)

### PUT /towatchlist/entries/{id}

#### Expected
Should replace or insert the record in the toWatchList.

#### Actual Output
![alt text](image-8.png)
![alt text](image-9.png)
![alt text](image-10.png)

### PATCH /towatchlist/entries/{id}/priority

#### Expected
Should update the priority of the specific movie.

#### Actual Output
![alt text](image-11.png)

### DELETE /towatchlist/entries/{id}

#### Expected
Should delete the specific movie from the user's toWatchList.

#### Actual Output
![alt text](image-12.png)
![alt text](image-13.png)

## completedWatchList

### GET /completedwatchlist/entries

#### Expected
Should return all entries on the user's completedWatchList.

#### Actual Output
![alt text](image-15.png)

### GET /completedwatchlist/entries/{id}/times-watched

#### Expected
Should return the number of times the user has watched the given movie.

#### Actual Output
![alt text](image-16.png)

### GET /completedwatchlist/entries/{id}/rating

#### Expected
Should return the user's rating for this specific movie.

#### Actual Output
![alt text](image-17.png)

### POST /completedwatchlist/entries

#### Expected
Should validate and insert the new entry into the completedWatchList and update the movie's average rating.

#### Actual Output
![alt text](image-14.png)

### PATCH /completedwatchlist/entries/{id}/rating

#### Expected
Should update the rating of the specific movie and recalculate the movie's average rating.

#### Actual Output
![alt text](image-19.png)

### PATCH /completedwatchlist/entries/{id}/times-watched

#### Expected
Should increment the number of times watched and update the last date watched.

#### Actual Output
![alt text](image-18.png)

### DELETE /completedwatchlist/entries/{id}

#### Expected
Should delete the specific movie from the user's completedWatchList.

#### Actual Output
![alt text](image-20.png)

## Users

### GET /users/{id}/stats

#### Expected
Should return basic watching stats for the provided user.

#### Actual Output
![alt text](image-21.png)

## Filters

### Example: GET /movies/?original_language=cn

#### Expected
Should return movies filtered by language.

#### Actual Output
![alt text](image-22.png)

### Example: GET /movies/?genres=Drama

#### Expected
Should return movies filtered by genres.

#### Actual Output
![alt text](image-23.png)

### Example: GET /movies/?title=man

#### Expected
Should return movies filtered by title.

#### Actual Output
![alt text](image-24.png)

### Example: GET /movies/?date_released=2002

#### Expected
Should return movies filtered by date.

#### Actual Output
![alt text](image-25.png)
