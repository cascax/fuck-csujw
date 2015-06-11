import numpy as np

def readImageData():
    f = open('data.txt')
    images = []
    for line in f.readlines():
        lineArr = [float(x) for x in line.strip().split()]
        images.append(lineArr)
    f.close()
    return np.mat(images)

def readTarget():
    f = open('target.txt')
    target = ''
    for line in f.readlines():
        target += line.strip()
    f.close()
    return list(target)

def test(gamma=0.01, n=400):
    from sklearn import svm, metrics

    datatarget = list(target)
    imagelen = len(images)
    dataimages = images.reshape(imagelen,-1)

    classifier = svm.SVC(gamma=gamma)
    classifier.fit(dataimages[:n], datatarget[:n])

    expected = datatarget[n:]
    predicted = classifier.predict(dataimages[n:])

    print("Classification report for classifier %s:\n%s\n"
          % (classifier, metrics.classification_report(expected, predicted)))
    print("Confusion matrix:\n%s" % metrics.confusion_matrix(expected, predicted))

    count = 0
    for e,p in zip(expected,predicted):
        if e!=p:
            print "is %s , not %s" % (e,p)
            count+=1
    print count


images = readImageData()
target = readTarget()
category = list('0123456789abcdefghigklmnopqrstuvwxyz')
