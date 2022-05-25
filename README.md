# Postie

Subscription Management Laravel Package



postie.subscriptions.index
    request params: null

postie.subscriptions.toggle
    request params:
        - string notification
        - array channels [telegram: true]


В Notification'е нужно подключить трейт Channelization и удалить метод via