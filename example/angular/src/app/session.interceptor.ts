import { Injectable } from '@angular/core';
import {
    HttpRequest,
    HttpHandler,
    HttpInterceptor, HttpErrorResponse
} from '@angular/common/http';
import { throwError } from 'rxjs/internal/observable/throwError';
import { finalize, tap } from 'rxjs/operators';
import { environment } from '../environments/environment';
import { AuthenticationService } from 'ng-kiniauth';

@Injectable()
export class SessionInterceptor implements HttpInterceptor {

    private static totalRequests = 0;

    constructor(private authService: AuthenticationService) {
    }

    intercept(request: HttpRequest<any>, next: HttpHandler) {
        SessionInterceptor.totalRequests++;
        this.authService.setLoadingRequest(true);

        request = request.clone({
            withCredentials: true
        });

        if (!request.url.startsWith('http')) {
            request = request.clone({
                url: environment.backendURL + request.url
            });
        }

        if (!request.headers.has('Content-Type')) {
            request = request.clone({ headers: request.headers.set('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8') });
        }

        const sessionData = this.authService.sessionData.getValue();
        if (sessionData && sessionData.csrfToken) {
            request = request.clone({ headers: request.headers.set('x-csrf-token', sessionData.csrfToken) });
        }

        return next.handle(request)
            .pipe(
                tap(
                    event => {
                    },
                    error => {
                        if (error instanceof HttpErrorResponse) {
                            return throwError(error);
                        }
                    }
                ),
                finalize(() => {
                    this.decreaseRequests();
                })
            );
    }

    public clearLoadingRequests() {
        SessionInterceptor.totalRequests = 0;
        this.authService.setLoadingRequest(false);
    }

    private decreaseRequests() {
        SessionInterceptor.totalRequests--;
        if (SessionInterceptor.totalRequests <= 0) {
            this.authService.setLoadingRequest(false);
        }
    }
}
